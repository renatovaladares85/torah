<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Policy\PolicyRule;

final class PolicyCatalog
{
    /** @var array<string, PolicyRule>|null */
   private ?array $builtIn = null;

   private readonly TicketFieldCatalog $ticketFields;

   public function __construct(
      private readonly CapabilityRegistry $capabilities,
      ?TicketFieldCatalog $ticketFields = null,
   ) {
      $this->ticketFields = $ticketFields ?? new TicketFieldCatalog();
   }

    /** @return array<string, PolicyRule> */
   public function all(): array {
       $rules = $this->builtIn();
      foreach ($this->capabilities->all() as $capability) {
         if (!isset($rules[$capability->key])) {
            $rules[$capability->key] = new PolicyRule(
                $capability->key,
                'external',
                $capability->label,
            );
         }
      }

       return $rules;
   }

   public function has(string $key): bool {
       return isset($this->all()[$key]);
   }

   public function get(string $key): ?PolicyRule {
       return $this->all()[$key] ?? null;
   }

    /** @return array<string, PolicyRule> */
   private function builtIn(): array {
      if ($this->builtIn !== null) {
          return $this->builtIn;
      }

       $definitions = [
           new PolicyRule('ticket.actor.requester', 'actors', __('Requester', 'torah'), [], ['[data-actor-type="requester"]', 'form[id^="addme_as_requester_"] button']),
           new PolicyRule('ticket.actor.observer', 'actors', __('Observer', 'torah'), [], ['[data-actor-type="observer"]', 'form[id^="addme_as_observer_"] button']),
           new PolicyRule('ticket.actor.assignee', 'actors', __('Assigned technician, group, or supplier', 'torah'), [], ['[data-actor-type="assign"]', 'form[id^="addme_as_assign_"] button']),
       ];

       foreach ($this->ticketFields->all() as $field) {
          foreach (['add', 'update'] as $action) {
             $definitions[] = new PolicyRule(
                sprintf('ticket.field.%s.%s', $field->key, $action),
                'properties',
                $field->label,
                [$field->inputKey],
                $field->selectors,
                'assistance',
                'ticket',
                $action,
             );
          }
       }

       $this->builtIn = [];
       foreach ($definitions as $definition) {
           $this->builtIn[$definition->key] = $definition;
       }

       return $this->builtIn;
   }

   /** @return list<TicketFieldDefinition> */
   public function ticketFields(): array {
       return $this->ticketFields->all();
   }
}
