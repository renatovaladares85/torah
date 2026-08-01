<?php

namespace GlpiPlugin\Torah\Application;

/** The single administrative/UI mapping for supported Ticket controls. */
final class TicketControlCatalog
{
   /** @return list<TicketControlDefinition> */
   public function all(): array {
      return [
         $this->field('opening_date', __('Opening date', 'torah'), 'opening_date', 'date', false, 'flatpickr'),
         $this->field('type', __('Type', 'torah'), 'type', 'type', false, 'select'),
         $this->field('category', __('Category', 'torah'), 'category', 'itilcategories_id', false, 'select2'),
         $this->field('status', __('Status', 'torah'), 'status', 'status', true, 'select'),
         $this->field('request_source', __('Request source', 'torah'), 'request_source', 'requesttypes_id', false, 'select2'),
         $this->field('urgency', __('Urgency', 'torah'), 'urgency', 'urgency', false, 'select'),
         $this->field('impact', __('Impact', 'torah'), 'impact', 'impact', false, 'select'),
         $this->field('priority', __('Priority', 'torah'), 'priority', 'priority', true, 'select'),
         $this->field('total_duration', __('Total duration', 'torah'), 'total_duration', 'actiontime'),
         new TicketControlDefinition('approval_request', __('Approval request', 'torah'), ['ticket.control.approval_request.add'], ['ticket.control.approval_request.update'], ['[name="_add_validation"]', '[name="users_id_validate"]'], false, 'composite'),
         $this->actor('requester', __('Requester', 'torah'), 'requester'),
         $this->actor('observer', __('Observer', 'torah'), 'observer'),
         $this->actor('assignee', __('Assigned to', 'torah'), 'assign'),
         new TicketControlDefinition('associated_items', __('Associated items', 'torah'), ['ticket.control.associated_items.add'], ['ticket.control.associated_items.update'], ['[name="itemtype"]', '[name="items_id"]'], false, 'composite'),
         $this->composite('tto', __('TTO', 'torah'), ['ticket.field.time_to_own.add', 'ticket.field.sla_tto.add'], ['ticket.field.time_to_own.update', 'ticket.field.sla_tto.update'], '[name="time_to_own"]', '[name="slas_id_tto"]'),
         $this->composite('ttr', __('TTR', 'torah'), ['ticket.field.solution_deadline.add', 'ticket.field.sla_ttr.add'], ['ticket.field.solution_deadline.update', 'ticket.field.sla_ttr.update'], '[name="time_to_resolve"]', '[name="slas_id_ttr"]'),
         $this->composite('internal_tto', __('Internal TTO', 'torah'), ['ticket.field.internal_time_to_own.add', 'ticket.field.ola_tto.add'], ['ticket.field.internal_time_to_own.update', 'ticket.field.ola_tto.update'], '[name="internal_time_to_own"]', '[name="olas_id_tto"]'),
         $this->composite('internal_ttr', __('Internal TTR', 'torah'), ['ticket.field.internal_solution_deadline.add', 'ticket.field.ola_ttr.add'], ['ticket.field.internal_solution_deadline.update', 'ticket.field.ola_ttr.update'], '[name="internal_time_to_resolve"]', '[name="olas_id_ttr"]'),
         new TicketControlDefinition('linked_tickets', __('Linked tickets', 'torah'), ['ticket.control.linked_tickets.add'], ['ticket.control.linked_tickets.update'], ['[name="_link"]', '[name="_linkedto"]'], false, 'relation'),
         new TicketControlDefinition('solution_date', __('Resolution date', 'torah'), [], ['ticket.field.solution_date.update'], ['[name="solvedate"]'], true, 'flatpickr'),
         new TicketControlDefinition('closing_date', __('Close date', 'torah'), [], ['ticket.field.closing_date.update'], ['[name="closedate"]'], true, 'flatpickr'),
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

   public function findByRuleKey(string $ruleKey): ?TicketControlDefinition {
      foreach ($this->all() as $definition) {
         if (in_array($ruleKey, [...$definition->addRuleKeys, ...$definition->updateRuleKeys], true)) {
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

   private function field(string $key, string $label, string $ruleKey, string $inputName, bool $sensitive = false, string $strategy = 'text'): TicketControlDefinition {
      return new TicketControlDefinition($key, $label, ["ticket.field.{$ruleKey}.add"], ["ticket.field.{$ruleKey}.update"], ["[name=\"{$inputName}\"]"], $sensitive, $strategy);
   }

   private function actor(string $key, string $label, string $selectorRole): TicketControlDefinition {
      return new TicketControlDefinition($key, $label, ["ticket.actor.{$key}.add"], ["ticket.actor.{$key}.update"], ["[data-actor-type=\"{$selectorRole}\"]", "form[id^=\"addme_as_{$selectorRole}_\"] button"], false, 'actor');
   }

   /**
    * @param list<string> $addRuleKeys
    * @param list<string> $updateRuleKeys
    */
   private function composite(string $key, string $label, array $addRuleKeys, array $updateRuleKeys, string $dateSelector, string $selectSelector): TicketControlDefinition {
      return new TicketControlDefinition(
         $key,
         $label,
         $addRuleKeys,
         $updateRuleKeys,
         [$dateSelector, $selectSelector],
         true,
         'composite',
         [
            ['strategy' => 'flatpickr', 'selectors' => [$dateSelector]],
            ['strategy' => 'select2', 'selectors' => [$selectSelector]],
         ],
      );
   }
}
