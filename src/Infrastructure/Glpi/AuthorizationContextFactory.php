<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use Session;
use Ticket;

final class AuthorizationContextFactory
{
   public function fromTicket(Ticket $ticket): ?AuthorizationContext {
      if (Session::isCron()) {
          return null;
      }

       $profileId = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
       $entityId = (int) ($ticket->fields['entities_id'] ?? 0);
       $ticketId = (int) ($ticket->fields['id'] ?? 0);
       $userId = (int) (Session::getLoginUserID() ?: 0);

      if ($profileId <= 0 || $ticketId <= 0 || $userId <= 0) {
          return null;
      }

       return new AuthorizationContext($profileId, $entityId, $ticketId, $userId);
   }
}
