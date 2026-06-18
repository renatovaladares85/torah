<?php

namespace GlpiPlugin\Torah\Application;

use CommonDBTM;
use CommonITILActor;
use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use GlpiPlugin\Torah\Infrastructure\Glpi\AuthorizationContextFactory;
use Group_Ticket;
use OlaLevel_Ticket;
use Session;
use SlaLevel_Ticket;
use Supplier_Ticket;
use Ticket;
use Ticket_Contract;
use Ticket_User;

final class TicketMutationGuard
{
   public function __construct(
        private readonly PolicyResolver $resolver,
        private readonly PolicyCatalog $catalog,
        private readonly AuthorizationContextFactory $contextFactory,
        private readonly ActorMutationDetector $actorDetector,
        private readonly FieldMutationDetector $fieldDetector,
        private readonly InternalMutationClassifier $internalMutationClassifier,
        private readonly AuditLogger $auditLogger,
    ) {
   }

   public function guardTicketUpdate(Ticket $ticket): bool {
       $context = $this->contextFactory->fromTicket($ticket);
      if ($context === null || !is_array($ticket->input)) {
          return true;
      }

       $input = $ticket->input;
      if ($this->internalMutationClassifier->isDerivedStatusUpdate($input)) {
          return true;
      }

      if (!$this->guardActorInput($ticket, $context, $input, 'ticket_update', false)) {
          return $this->cancel($ticket);
      }

       return $this->guardFieldInput($ticket, $context, $ticket->fields, $input, 'update', 'ticket_update');
   }

   public function guardTicketAdd(Ticket $ticket): bool {
      if (!is_array($ticket->input)) {
          return true;
      }

       $input = $ticket->input;
       $context = $this->contextFactory->fromTicketInput($ticket, $input);
      if ($context === null) {
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
   ): bool {
      foreach ($this->catalog->all() as $rule) {
         if ($rule->group !== 'properties' || $rule->object !== 'ticket' || $rule->action !== $action) {
             continue;
         }

         foreach ($rule->inputKeys as $inputKey) {
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
       $ticketId = (int) ($item->input['tickets_id'] ?? $item->fields['tickets_id'] ?? 0);
      if ($ticketId <= 0) {
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

       $role = $this->relationRole($item);
      if ($source === 'relation_add' && $role !== null && !$this->allowsActorItemtypes(
          $context,
          $role,
          [$this->relationItemtype($item)],
          $source,
      )) {
          return $this->cancel($item);
      }

       $ruleKey = $this->relationRuleKey($item);
      if ($ruleKey === null || $this->allow($context, $ruleKey, $source)) {
          return true;
      }

       return $this->cancel($item);
   }

   public function guardLevelAgreementDeletion(CommonDBTM $item): bool {
      if (!is_array($item->input)) {
          return true;
      }

       $isSla = $item instanceof SlaLevel_Ticket && isset($_POST['sla_delete']);
       $isOla = $item instanceof OlaLevel_Ticket && isset($_POST['ola_delete']);
      if (!$isSla && !$isOla) {
          return true;
      }

       $ticketId = (int) ($_POST['id'] ?? $item->fields['tickets_id'] ?? 0);
       $type = (int) ($_POST['type'] ?? 0);
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
      if ($isSla && !empty($_POST['delete_date'])) {
          $ruleKeys[] = $type === \SLM::TTO
              ? 'ticket.field.time_to_own.update'
              : 'ticket.field.solution_deadline.update';
      }

      foreach ($ruleKeys as $ruleKey) {
         if (!$this->allow($context, $ruleKey, 'level_agreement_delete')) {
             return $this->cancel($item);
         }
      }

       return true;
   }

   private function allow(AuthorizationContext $context, string $ruleKey, string $source): bool {
       $decision = $this->resolver->decide($context, $ruleKey);
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

          $key = 'ticket.actor.' . ($role === 'assign' ? 'assignee' : $role);
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

       $policy = $this->resolver->resolve($context);
      if ($policy === null) {
          return true;
      }

       $allowed = array_fill_keys(ActorItemtypePolicy::allowedFor($policy, $role), true);
      foreach ($itemtypes as $itemtype) {
         if (!isset($allowed[$itemtype])) {
             $this->auditLogger->denied(
                 $context,
                 ActorItemtypePolicy::optionKey($role),
                 $policy->id,
                 $source,
             );

             return false;
         }
      }

       return true;
   }

   private function relationRuleKey(CommonDBTM $item): ?string {
      if ($item instanceof Ticket_Contract) {
          return 'ticket.field.contract.update';
      }

      if (!$item instanceof Ticket_User && !$item instanceof Group_Ticket && !$item instanceof Supplier_Ticket) {
          return null;
      }

       $role = $this->relationRole($item);

       return match ($role) {
           'requester' => 'ticket.actor.requester',
           'observer'  => 'ticket.actor.observer',
           'assign'    => 'ticket.actor.assignee',
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
       Session::addMessageAfterRedirect(
           __('Torah blocked this change according to the active profile and entity policy.', 'torah'),
           false,
           ERROR,
       );

       return false;
   }
}
