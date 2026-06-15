<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Policy\PolicyRule;

final class PolicyCatalog
{
    /** @var array<string, PolicyRule>|null */
   private ?array $builtIn = null;

   public function __construct(private readonly CapabilityRegistry $capabilities) {
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
           new PolicyRule('ticket.field.opening_date', 'properties', __('Opening date', 'torah'), ['date'], ['[name="date"]']),
           new PolicyRule('ticket.field.solution_deadline', 'properties', __('Time to resolve', 'torah'), ['time_to_resolve'], ['[name="time_to_resolve"]']),
           new PolicyRule('ticket.field.solution_date', 'properties', __('Solution date', 'torah'), ['solvedate'], ['[name="solvedate"]']),
           new PolicyRule('ticket.field.closing_date', 'properties', __('Closing date', 'torah'), ['closedate'], ['[name="closedate"]']),
           new PolicyRule('ticket.field.type', 'properties', __('Type', 'torah'), ['type'], ['[name="type"]']),
           new PolicyRule('ticket.field.category', 'properties', __('Category', 'torah'), ['itilcategories_id'], ['[name="itilcategories_id"]']),
           new PolicyRule('ticket.field.status', 'properties', __('Status', 'torah'), ['status'], ['[name="status"]']),
           new PolicyRule('ticket.field.request_source', 'properties', __('Request source', 'torah'), ['requesttypes_id'], ['[name="requesttypes_id"]']),
           new PolicyRule('ticket.field.urgency', 'properties', __('Urgency', 'torah'), ['urgency'], ['[name="urgency"]']),
           new PolicyRule('ticket.field.impact', 'properties', __('Impact', 'torah'), ['impact'], ['[name="impact"]']),
           new PolicyRule('ticket.field.priority', 'properties', __('Priority', 'torah'), ['priority'], ['[name="priority"]']),
           new PolicyRule('ticket.field.location', 'properties', __('Location', 'torah'), ['locations_id'], ['[name="locations_id"]']),
           new PolicyRule('ticket.field.contract', 'properties', __('Contract', 'torah'), ['_contracts_id'], ['[name="_contracts_id"]']),
           new PolicyRule('ticket.field.total_duration', 'properties', __('Total duration', 'torah'), ['actiontime'], ['[name="actiontime"]']),
         new PolicyRule('ticket.field.sla_tto', 'properties', __('SLA for time to own', 'torah'), ['slas_id_tto'], ['[name="slas_id_tto"]', 'label[for^="slas_id_tto_"]']),
         new PolicyRule('ticket.field.sla_ttr', 'properties', __('SLA for time to resolve', 'torah'), ['slas_id_ttr'], ['[name="slas_id_ttr"]', 'label[for^="slas_id_ttr_"]']),
         new PolicyRule('ticket.field.ola_tto', 'properties', __('OLA for time to own', 'torah'), ['olas_id_tto'], ['[name="olas_id_tto"]', 'label[for^="olas_id_tto_"]']),
         new PolicyRule('ticket.field.ola_ttr', 'properties', __('OLA for time to resolve', 'torah'), ['olas_id_ttr'], ['[name="olas_id_ttr"]', 'label[for^="olas_id_ttr_"]']),
           new PolicyRule('ticket.field.time_to_own', 'properties', __('Time to own', 'torah'), ['time_to_own'], ['[name="time_to_own"]']),
       ];

       $this->builtIn = [];
       foreach ($definitions as $definition) {
           $this->builtIn[$definition->key] = $definition;
       }

       return $this->builtIn;
   }
}
