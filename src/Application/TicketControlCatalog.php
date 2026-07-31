<?php

namespace GlpiPlugin\Torah\Application;

/** The single administrative/UI mapping for supported Ticket controls. */
final class TicketControlCatalog
{
   /** @return list<TicketControlDefinition> */
   public function all(): array {
      return [
         $this->field('opening_date', __('Opening date', 'torah'), 'opening_date', 'date'),
         $this->field('type', __('Type', 'torah'), 'type', 'type'),
         $this->field('category', __('Category', 'torah'), 'category', 'itilcategories_id'),
         $this->field('status', __('Status', 'torah'), 'status', 'status', true),
         $this->field('request_source', __('Request source', 'torah'), 'request_source', 'requesttypes_id'),
         $this->field('urgency', __('Urgency', 'torah'), 'urgency', 'urgency'),
         $this->field('impact', __('Impact', 'torah'), 'impact', 'impact'),
         $this->field('priority', __('Priority', 'torah'), 'priority', 'priority', true),
         $this->field('total_duration', __('Total duration', 'torah'), 'total_duration', 'actiontime'),
         new TicketControlDefinition('approval_request', __('Approval request', 'torah'), ['ticket.control.approval_request.add'], ['ticket.control.approval_request.update'], ['[name="_add_validation"]', '[name="users_id_validate"]']),
         $this->actor('requester', __('Requester', 'torah'), 'requester'),
         $this->actor('observer', __('Observer', 'torah'), 'observer'),
         $this->actor('assignee', __('Assigned to', 'torah'), 'assign'),
         new TicketControlDefinition('associated_items', __('Associated items', 'torah'), ['ticket.control.associated_items.add'], ['ticket.control.associated_items.update'], ['[name="itemtype"]', '[name="items_id"]']),
         new TicketControlDefinition('tto', __('TTO', 'torah'), ['ticket.field.time_to_own.add', 'ticket.field.sla_tto.add'], ['ticket.field.time_to_own.update', 'ticket.field.sla_tto.update'], ['[name="time_to_own"]', '[name="slas_id_tto"]'], true),
         new TicketControlDefinition('ttr', __('TTR', 'torah'), ['ticket.field.solution_deadline.add', 'ticket.field.sla_ttr.add'], ['ticket.field.solution_deadline.update', 'ticket.field.sla_ttr.update'], ['[name="time_to_resolve"]', '[name="slas_id_ttr"]'], true),
         new TicketControlDefinition('internal_tto', __('Internal TTO', 'torah'), ['ticket.field.internal_time_to_own.add', 'ticket.field.ola_tto.add'], ['ticket.field.internal_time_to_own.update', 'ticket.field.ola_tto.update'], ['[name="internal_time_to_own"]', '[name="olas_id_tto"]'], true),
         new TicketControlDefinition('internal_ttr', __('Internal TTR', 'torah'), ['ticket.field.internal_solution_deadline.add', 'ticket.field.ola_ttr.add'], ['ticket.field.internal_solution_deadline.update', 'ticket.field.ola_ttr.update'], ['[name="internal_time_to_resolve"]', '[name="olas_id_ttr"]'], true),
         new TicketControlDefinition('linked_tickets', __('Linked tickets', 'torah'), ['ticket.control.linked_tickets.add'], ['ticket.control.linked_tickets.update'], ['[name="_link"]', '[name="_linkedto"]']),
      ];
   }

   public function get(string $key): ?TicketControlDefinition {
      foreach ($this->all() as $definition) {
         if ($definition->key === $key) {
            return $definition;
         }
      }
      return null;
   }

   /**
    * @param list<string> $controlKeys
    * @return list<string>
    */
   public function expand(array $controlKeys, string $action): array {
      $rules = [];
      foreach ($controlKeys as $key) {
         if (!is_string($key) || ($control = $this->get($key)) === null) {
            throw new \InvalidArgumentException('The request contains an unknown ticket control.');
         }
         foreach ($action === 'add' ? $control->addRuleKeys : $control->updateRuleKeys as $rule) {
            $rules[$rule] = true;
         }
      }
      return array_keys($rules);
   }

   private function field(string $key, string $label, string $ruleKey, string $inputName, bool $sensitive = false): TicketControlDefinition {
      return new TicketControlDefinition($key, $label, ["ticket.field.{$ruleKey}.add"], ["ticket.field.{$ruleKey}.update"], ["[name=\"{$inputName}\"]"], $sensitive);
   }

   private function actor(string $key, string $label, string $selectorRole): TicketControlDefinition {
      return new TicketControlDefinition($key, $label, ["ticket.actor.{$key}.add"], ["ticket.actor.{$key}.update"], ["[data-actor-type=\"{$selectorRole}\"]", "form[id^=\"addme_as_{$selectorRole}_\"] button"]);
   }
}
