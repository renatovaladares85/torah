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

      foreach (['requester', 'observer', 'assign'] as $role) {
         if ($this->actorDetector->hasMutation($ticket, $input, $role)) {
             $key = 'ticket.actor.' . ($role === 'assign' ? 'assignee' : $role);
            if (!$this->allow($context, $key, 'ticket_update')) {
               return $this->cancel($ticket);
            }
         }
      }

      foreach ($this->catalog->all() as $rule) {
         if ($rule->group !== 'properties') {
             continue;
         }

         foreach ($rule->inputKeys as $inputKey) {
            if (!$this->fieldDetector->changes($ticket->fields, $input, $inputKey)) {
                continue;
            }

            if (!$this->allow($context, $rule->key, 'ticket_update')) {
                return $this->cancel($ticket);
            }
         }
      }

       return true;
   }

   public function guardRelationMutation(CommonDBTM $item): bool {
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

       $ruleKey = $this->relationRuleKey($item);
      if ($ruleKey === null || $this->allow($context, $ruleKey, 'relation_mutation')) {
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
       $ruleKeys = [sprintf('ticket.field.%s_%s', $isSla ? 'sla' : 'ola', $suffix)];
      if ($isSla && !empty($_POST['delete_date'])) {
          $ruleKeys[] = $type === \SLM::TTO
              ? 'ticket.field.time_to_own'
              : 'ticket.field.solution_deadline';
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

   private function relationRuleKey(CommonDBTM $item): ?string {
      if ($item instanceof Ticket_Contract) {
          return 'ticket.field.contract';
      }

      if (!$item instanceof Ticket_User && !$item instanceof Group_Ticket && !$item instanceof Supplier_Ticket) {
          return null;
      }

       $type = (int) ($item->input['type'] ?? $item->fields['type'] ?? 0);

       return match ($type) {
           CommonITILActor::REQUESTER => 'ticket.actor.requester',
           CommonITILActor::OBSERVER  => 'ticket.actor.observer',
           CommonITILActor::ASSIGN    => 'ticket.actor.assignee',
           default                    => null,
       };
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
