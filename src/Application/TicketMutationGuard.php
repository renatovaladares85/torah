<?php

namespace GlpiPlugin\Torah\Application;

use CommonDBTM;
use CommonITILActor;
use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use GlpiPlugin\Torah\Infrastructure\Glpi\AuthorizationContextFactory;
use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiGlobalActorSettingsStore;
use GlpiPlugin\Torah\Infrastructure\Glpi\TicketCreationContext;
use Group_Ticket;
use Item_Ticket;
use OlaLevel_Ticket;
use Session;
use SlaLevel_Ticket;
use Supplier_Ticket;
use Ticket;
use Ticket_Contract;
use Ticket_Ticket;
use Ticket_User;
use TicketValidation;

final class TicketMutationGuard
{
   public function __construct(
        private readonly PolicyResolver $resolver,
        private readonly PolicyCatalog $catalog,
        private readonly AuthorizationContextFactory $contextFactory,
        private readonly ActorMutationDetector $actorDetector,
        private readonly FieldMutationDetector $fieldDetector,
        private readonly AuditLogger $auditLogger,
        private readonly GlpiGlobalActorSettingsStore $globalActorSettings,
    ) {
   }

   public function guardTicketUpdate(Ticket $ticket): bool {
      $context = $this->contextFactory->fromTicket($ticket);
      if ($context === null || !is_array($ticket->input)) {
         if ($context === null) {
            $this->auditLogger->contextUnresolved((int) ($ticket->fields['entities_id'] ?? 0), (int) ($ticket->fields['id'] ?? 0), 'ticket_update');
         }
          return true;
      }

       $input = $ticket->input;
      if (!$this->guardActorInput($ticket, $context, $input, 'ticket_update', false)) {
          return $this->cancel($ticket);
      }

      if (!$this->guardFieldInput($ticket, $context, $ticket->fields, $input, 'update', 'ticket_update')) {
         return false;
      }
      if ($this->fieldDetector->changes($ticket->fields, $input, 'entities_id')) {
         $destination = $this->contextFactory->fromTicketInput($ticket, $input);
         if ($destination !== null && !$this->guardFieldInput($ticket, $destination, $ticket->fields, $input, 'update', 'ticket_entity_destination', ['entities_id'])) {
            return false;
         }
      }
       return true;
   }

   public function guardTicketAdd(Ticket $ticket): bool {
      if (!is_array($ticket->input)) {
          return true;
      }

       $input = $ticket->input;
       $context = $this->contextFactory->fromTicketInput($ticket, $input);
      if ($context === null) {
          $this->auditLogger->contextUnresolved((int) ($input['entities_id'] ?? 0), 0, 'ticket_add');
          return true;
      }

      if (!$this->guardActorInput($ticket, $context, $input, 'ticket_add', true)) {
          return $this->cancel($ticket);
      }

       return $this->guardFieldInput($ticket, $context, [], $input, 'add', 'ticket_add');
   }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $input
     */
   private function guardFieldInput(
      Ticket $ticket,
      AuthorizationContext $context,
      array $existing,
      array $input,
      string $action,
      string $source,
      array $ignoredInputKeys = [],
   ): bool {
      foreach ($this->catalog->all() as $rule) {
         if ($rule->group !== 'properties' || $rule->object !== 'ticket' || $rule->action !== $action) {
             continue;
         }

         foreach ($rule->inputKeys as $inputKey) {
            if (in_array($inputKey, $ignoredInputKeys, true)) {
               continue;
            }
            if (!$this->fieldDetector->changes($existing, $input, $inputKey)) {
                continue;
            }

            if (!$this->allow($context, $rule->key, $source)) {
                return $this->cancel($ticket);
            }
         }
      }

       return true;
   }

   public function guardRelationMutation(CommonDBTM $item, string $source = 'relation_mutation'): bool {
      foreach ($this->relationTicketIds($item) as $ticketId) {
         $ticket = new Ticket();
         if (!$ticket->getFromDB($ticketId)) {
            continue;
         }
         $context = $this->contextFactory->fromTicket($ticket);
         if ($context === null) {
            $this->auditLogger->contextUnresolved((int) ($ticket->fields['entities_id'] ?? 0), $ticketId, $source);
            continue;
         }
         $action = TicketCreationContext::contains($ticketId) ? 'add' : 'update';
         $role = $this->relationRole($item);
         if ($source === 'relation_add' && $role !== null && !$this->allowsActorItemtypes($context, $role, [$this->relationItemtype($item)], $source)) {
            return $this->cancel($item);
         }
         $ruleKey = $this->relationRuleKey($item, $action);
         if ($ruleKey !== null && !$this->allow($context, $ruleKey, $source)) {
            return $this->cancel($item);
         }
      }
      return true;
   }

   public function guardLevelAgreementDeletion(CommonDBTM $item): bool {
       $isSla = $item instanceof SlaLevel_Ticket;
       $isOla = $item instanceof OlaLevel_Ticket;
      if (!$isSla && !$isOla) {
          return true;
      }

       $ticketId = (int) ($item->input['tickets_id'] ?? $item->fields['tickets_id'] ?? 0);
       $type = (int) ($item->input['type'] ?? $item->fields['type'] ?? 0);
      if ($ticketId <= 0 || $type <= 0) {
          return true;
      }

       $ticket = new Ticket();
      if (!$ticket->getFromDB($ticketId)) {
          return true;
      }

       $context = $this->contextFactory->fromTicket($ticket);
      if ($context === null) {
          return true;
      }

       $suffix = $type === \SLM::TTO ? 'tto' : 'ttr';
       $ruleKeys = [sprintf('ticket.field.%s_%s.update', $isSla ? 'sla' : 'ola', $suffix)];
       $ruleKeys[] = $type === \SLM::TTO
           ? ($isSla ? 'ticket.field.time_to_own.update' : 'ticket.field.internal_time_to_own.update')
           : ($isSla ? 'ticket.field.solution_deadline.update' : 'ticket.field.internal_solution_deadline.update');

      foreach ($ruleKeys as $ruleKey) {
         if (!$this->allow($context, $ruleKey, 'level_agreement_delete')) {
             return $this->cancel($item);
         }
      }

       return true;
   }

   private function allow(AuthorizationContext $context, string $ruleKey, string $source): bool {
      try {
         $decision = $this->resolver->decideBackend($context, $ruleKey, $this->catalog);
      } catch (\Throwable) {
         $this->auditLogger->evaluationError($context, $source);
         return true;
      }
      if ($decision->allowed) {
          return true;
      }

       $this->auditLogger->denied($context, $ruleKey, $decision->policySetId, $source);

       return false;
   }

    /** @param array<string, mixed> $input */
   private function guardActorInput(
       Ticket $ticket,
       AuthorizationContext $context,
       array $input,
       string $source,
       bool $newTicket,
   ): bool {
      foreach (['requester', 'observer', 'assign'] as $role) {
          $addedItemtypes = $newTicket
              ? $this->actorDetector->addedItemtypesForNewTicket($input, $role)
              : $this->actorDetector->addedItemtypes($ticket, $input, $role);
         if (!$this->allowsActorItemtypes($context, $role, $addedItemtypes, $source)) {
             return false;
         }

          $hasMutation = $newTicket
              ? $addedItemtypes !== []
              : $this->actorDetector->hasMutation($ticket, $input, $role);
         if (!$hasMutation) {
             continue;
         }

          $key = 'ticket.actor.' . ($role === 'assign' ? 'assignee' : $role) . '.' . ($newTicket ? 'add' : 'update');
         if (!$this->allow($context, $key, $source)) {
             return false;
         }
      }

       return true;
   }

    /** @param list<string> $itemtypes */
   private function allowsActorItemtypes(AuthorizationContext $context, string $role, array $itemtypes, string $source): bool {
      if ($itemtypes === []) {
          return true;
      }

       $allowed = array_fill_keys($this->globalActorSettings->all()[$role] ?? GlobalActorItemtypePolicy::ITEMTYPES, true);
      foreach ($itemtypes as $itemtype) {
         if (!isset($allowed[$itemtype])) {
             $this->auditLogger->actorItemtypeDenied($context, $role, $itemtype, $source);

             return false;
         }
      }

       return true;
   }

   private function relationRuleKey(CommonDBTM $item, string $action = 'update'): ?string {
      if ($item instanceof Ticket_Contract) {
          return 'ticket.field.contract.update';
      }

      if ($item instanceof Item_Ticket) {
         return "ticket.control.associated_items.{$action}";
      }

      if ($item instanceof Ticket_Ticket) {
         return "ticket.control.linked_tickets.{$action}";
      }

      if ($item instanceof TicketValidation) {
         return "ticket.control.approval_request.{$action}";
      }

      if (!$item instanceof Ticket_User && !$item instanceof Group_Ticket && !$item instanceof Supplier_Ticket) {
          return null;
      }

       $role = $this->relationRole($item);

       return match ($role) {
           'requester' => "ticket.actor.requester.{$action}",
           'observer'  => "ticket.actor.observer.{$action}",
           'assign'    => "ticket.actor.assignee.{$action}",
           default     => null,
       };
   }

   private function relationRole(CommonDBTM $item): ?string {
      if (!$item instanceof Ticket_User && !$item instanceof Group_Ticket && !$item instanceof Supplier_Ticket) {
          return null;
      }

       $type = (int) ($item->input['type'] ?? $item->fields['type'] ?? 0);

       return match ($type) {
           CommonITILActor::REQUESTER => 'requester',
           CommonITILActor::OBSERVER  => 'observer',
           CommonITILActor::ASSIGN    => 'assign',
           default                    => null,
       };
   }

   /** @return list<int> */
   private function relationTicketIds(CommonDBTM $item): array {
      $input = is_array($item->input) ? $item->input : [];
      $fields = is_array($item->fields) ? $item->fields : [];
      $ids = [];
      foreach (['tickets_id', 'tickets_id_1', 'tickets_id_2'] as $key) {
         $id = (int) ($input[$key] ?? $fields[$key] ?? 0);
         if ($id > 0) {
            $ids[$id] = true;
         }
      }
      return array_keys($ids);
   }

   private function relationItemtype(CommonDBTM $item): string {
      if ($item instanceof Group_Ticket) {
          return 'Group';
      }

      if ($item instanceof Supplier_Ticket) {
          return 'Supplier';
      }

       return 'User';
   }

   private function cancel(CommonDBTM $item): false {
       $item->input = false;
      if ($this->isInteractiveSession()) {
         Session::addMessageAfterRedirect(
           __('Torah blocked this change according to the active profile and entity policy.', 'torah'),
           false,
           ERROR,
         );
      }

       return false;
   }

   private function isInteractiveSession(): bool {
      if (Session::isCron()) {
         return false;
      }
      if (method_exists(Session::class, 'isAPI')) {
         try {
            return !Session::isAPI();
         } catch (\Throwable) {
            return false;
         }
      }
      return true;
   }
}
