<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use Session;
use Ticket;

final class AuthorizationContextFactory
{
   public function fromTicket(Ticket $ticket): ?AuthorizationContext {
       $context = $this->fromTicketInput($ticket, []);
      if ($context === null || $context->ticketId <= 0) {
          return null;
      }

       return $context;
   }

    /** @param array<string, mixed> $input */
   public function fromTicketInput(Ticket $ticket, array $input): ?AuthorizationContext {
      if (Session::isCron()) {
          return null;
      }

       $profileId = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
       $entityId = (int) ($input['entities_id'] ?? $ticket->fields['entities_id'] ?? $_SESSION['glpiactive_entity'] ?? 0);
       $ticketId = (int) ($ticket->fields['id'] ?? $input['id'] ?? 0);
       $userId = (int) (Session::getLoginUserID() ?: 0);

      if ($profileId <= 0 || $entityId < 0 || $userId <= 0) {
          return null;
      }

       return new AuthorizationContext($profileId, $entityId, $ticketId, $userId);
   }
}
