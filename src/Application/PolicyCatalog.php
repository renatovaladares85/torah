<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Policy\PolicyRule;

final class PolicyCatalog
{
    /** @var array<string, PolicyRule>|null */
   private ?array $builtIn = null;

   private readonly TicketFieldCatalog $ticketFields;

   private readonly TicketControlCatalog $ticketControls;

   public function __construct(
      private readonly CapabilityRegistry $capabilities,
      ?TicketFieldCatalog $ticketFields = null,
      ?TicketControlCatalog $ticketControls = null,
   ) {
      $this->ticketFields = $ticketFields ?? new TicketFieldCatalog();
      $this->ticketControls = $ticketControls ?? new TicketControlCatalog();
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

   public function findControlByRuleKey(string $ruleKey): ?TicketControlDefinition {
      return $this->ticketControls->findByRuleKey($ruleKey);
   }

   public function labelForRuleKey(string $ruleKey): ?string {
      return $this->findControlByRuleKey($ruleKey)?->label ?? $this->get($ruleKey)?->label;
   }

    /** @return array<string, PolicyRule> */
   private function builtIn(): array {
      if ($this->builtIn !== null) {
          return $this->builtIn;
      }

       $definitions = [];
      foreach ([
           'requester' => [__('Requester', 'torah'), 'requester'],
           'observer'  => [__('Observer', 'torah'), 'observer'],
           'assignee'  => [__('Assigned technician, group, or supplier', 'torah'), 'assign'],
       ] as $role => [$label, $selectorRole]) {
         // Deprecated aliases are retained for safe read compatibility. The
         // installer converts persisted aliases to the action-specific rules.
         $definitions[] = new PolicyRule("ticket.actor.{$role}", 'legacy', $label);
         foreach (['add', 'update'] as $action) {
            $definitions[] = new PolicyRule(
                "ticket.actor.{$role}.{$action}",
                'actors',
                $label,
                [],
                ["[data-actor-type=\"{$selectorRole}\"]", "form[id^=\"addme_as_{$selectorRole}_\"] button"],
                'assistance',
                'ticket',
                $action,
            );
         }
      }

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

       $definitions[] = new PolicyRule('ticket.control.approval_request.add', 'properties', __('Approval request', 'torah'), ['_add_validation', 'users_id_validate'], ['[name="_add_validation"]', '[name="users_id_validate"]'], 'assistance', 'ticket', 'add');
       $definitions[] = new PolicyRule('ticket.control.approval_request.update', 'properties', __('Approval request', 'torah'), [], [], 'assistance', 'ticket', 'update');
       $definitions[] = new PolicyRule('ticket.control.associated_items.add', 'properties', __('Associated items', 'torah'), ['itemtype', 'items_id'], ['[name="itemtype"]', '[name="items_id"]'], 'assistance', 'ticket', 'add');
       $definitions[] = new PolicyRule('ticket.control.associated_items.update', 'properties', __('Associated items', 'torah'), [], [], 'assistance', 'ticket', 'update');
       $definitions[] = new PolicyRule('ticket.control.linked_tickets.add', 'properties', __('Linked tickets', 'torah'), ['_link', '_linkedto'], ['[name="_link"]', '[name="_linkedto"]'], 'assistance', 'ticket', 'add');
       $definitions[] = new PolicyRule('ticket.control.linked_tickets.update', 'properties', __('Linked tickets', 'torah'), ['_link', '_linkedto'], ['[name="_link"]', '[name="_linkedto"]'], 'assistance', 'ticket', 'update');

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
