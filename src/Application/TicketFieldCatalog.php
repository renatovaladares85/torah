<?php

namespace GlpiPlugin\Torah\Application;

final class TicketFieldCatalog
{
   private const KEY_ALIASES = [
       'date'                     => 'opening_date',
       'time_to_resolve'          => 'solution_deadline',
       'solvedate'                => 'solution_date',
       'closedate'                => 'closing_date',
       'itilcategories_id'        => 'category',
       'requesttypes_id'          => 'request_source',
       'locations_id'             => 'location',
       '_contracts_id'            => 'contract',
       'actiontime'               => 'total_duration',
       'slas_id_tto'              => 'sla_tto',
       'slas_id_ttr'              => 'sla_ttr',
       'olas_id_tto'              => 'ola_tto',
       'olas_id_ttr'              => 'ola_ttr',
       'internal_time_to_resolve' => 'internal_solution_deadline',
       'internal_time_to_own'     => 'internal_time_to_own',
       'itemtype'                 => 'associated_item_type',
       'items_id'                 => 'associated_item',
       'entities_id'              => 'entity',
       'users_id_recipient'       => 'users_id_recipient',
       'global_validation'        => 'approval_status',
   ];

   private const EXCLUDED_DEFAULT_KEYS = [
       '_actors',
       '_add_validation',
       '_content',
       '_documents_id',
       '_filename',
       '_groups_id_assign',
       '_groups_id_observer',
       '_groups_id_requester',
       '_itil_assign',
       '_itil_observer',
       '_itil_requester',
       '_link',
       '_skip_auto_assign',
       '_skip_default_actor',
       '_skip_promoted_fields',
       '_skip_sla_assign',
       '_suppliers_id_assign',
       '_suppliers_id_assign_notif',
       '_tag_content',
       '_tag_filename',
       '_tasktemplates_id',
       '_users_id_assign',
       '_users_id_assign_notif',
       '_users_id_observer',
       '_users_id_observer_notif',
       '_users_id_requester',
       '_users_id_requester_notif',
       'followup',
       'plan',
       'users_id_validate',
   ];

   /** @var list<TicketFieldDefinition>|null */
   private ?array $fields = null;

   /** @return list<TicketFieldDefinition> */
   public function all(): array {
      if ($this->fields !== null) {
          return $this->fields;
      }

      $labels = $this->fallbackLabels();
      foreach ($this->glpiEditableFields() as $inputKey => $label) {
          $labels[$inputKey] = $label;
      }

      $fields = [];
      foreach ($labels as $inputKey => $label) {
          $fields[] = new TicketFieldDefinition(
              $this->keyForInput($inputKey),
              $label,
              $inputKey,
              $this->selectorsForInput($inputKey),
          );
      }

      usort($fields, static fn (TicketFieldDefinition $left, TicketFieldDefinition $right): int => strnatcasecmp($left->label, $right->label));

      return $this->fields = $fields;
   }

   private function keyForInput(string $inputKey): string {
       return self::KEY_ALIASES[$inputKey] ?? trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($inputKey)), '_');
   }

   /** @return array<string, string> */
   private function fallbackLabels(): array {
       return [
           'name'                     => __('Title', 'torah'),
           'content'                  => __('Description', 'torah'),
           'date'                     => __('Opening date', 'torah'),
           'type'                     => __('Type', 'torah'),
           'itilcategories_id'        => __('Category', 'torah'),
           'status'                   => __('Status', 'torah'),
           'requesttypes_id'          => __('Request source', 'torah'),
           'urgency'                  => __('Urgency', 'torah'),
           'impact'                   => __('Impact', 'torah'),
           'priority'                 => __('Priority', 'torah'),
           'locations_id'             => __('Location', 'torah'),
           'actiontime'               => __('Total duration', 'torah'),
           'time_to_resolve'          => __('Time to resolve', 'torah'),
           'time_to_own'              => __('Time to own', 'torah'),
           'solvedate'                => __('Resolution date', 'torah'),
           'closedate'                => __('Close date', 'torah'),
           'slas_id_tto'              => __('SLA for time to own', 'torah'),
           'slas_id_ttr'              => __('SLA for time to resolve', 'torah'),
           'olas_id_tto'              => __('OLA for time to own', 'torah'),
           'olas_id_ttr'              => __('OLA for time to resolve', 'torah'),
           'internal_time_to_resolve' => __('Internal time to resolve', 'torah'),
           'internal_time_to_own'     => __('Internal time to own', 'torah'),
           '_contracts_id'            => __('Contract', 'torah'),
           'itemtype'                 => __('Associated item type', 'torah'),
           'items_id'                 => __('Associated item', 'torah'),
           'entities_id'              => __('Entity', 'torah'),
           'users_id_recipient'       => __('By', 'torah'),
           'global_validation'        => __('Approval status', 'torah'),
       ];
   }

   /** @return array<string, string> */
   private function glpiEditableFields(): array {
      if (!class_exists('Ticket') || !method_exists('Ticket', 'getDefaultValues')) {
          return [];
      }

      try {
          $defaults = \Ticket::getDefaultValues();
      } catch (\Throwable) {
          return [];
      }

      if (!is_array($defaults)) {
          return [];
      }

       $searchLabels = $this->glpiSearchLabels();
       $fields = [];
      foreach (array_keys($defaults) as $inputKey) {
         if (!is_string($inputKey) || !$this->isEditableInput($inputKey, $defaults[$inputKey])) {
             continue;
         }

          $fields[$inputKey] = $searchLabels[$inputKey] ?? $this->humanize($inputKey);
      }

       return $fields;
   }

   /** @return array<string, string> */
   private function glpiSearchLabels(): array {
      if (!class_exists('Ticket')) {
          return [];
      }

      try {
          $ticket = new \Ticket();
          $options = method_exists($ticket, 'rawSearchOptions') ? $ticket->rawSearchOptions() : [];
      } catch (\Throwable) {
          return [];
      }

      if (!is_array($options)) {
          return [];
      }

       $labels = [];
      foreach ($options as $option) {
         if (!is_array($option) || !isset($option['name'])) {
             continue;
         }

          $field = (string) ($option['linkfield'] ?? $option['field'] ?? '');
         if ($field === '') {
             continue;
         }

          $labels[$field] = (string) $option['name'];
      }

       return $labels;
   }

   private function isEditableInput(string $inputKey, mixed $value): bool {
      if (in_array($inputKey, self::EXCLUDED_DEFAULT_KEYS, true)) {
          return false;
      }

      if (str_starts_with($inputKey, '_') && $inputKey !== '_contracts_id') {
          return false;
      }

       return is_scalar($value) || $value === null;
   }

   private function humanize(string $inputKey): string {
       return ucwords(str_replace('_', ' ', trim($inputKey, '_')));
   }

   /** @return list<string> */
   private function selectorsForInput(string $inputKey): array {
       $escaped = addcslashes($inputKey, '\\"');

       return [sprintf('[name="%s"]', $escaped)];
   }
}
