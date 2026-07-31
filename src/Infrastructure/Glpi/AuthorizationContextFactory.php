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
       $profileId = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
       $entityId = (int) ($input['entities_id'] ?? $ticket->fields['entities_id'] ?? $_SESSION['glpiactive_entity'] ?? 0);
       $ticketId = (int) ($ticket->fields['id'] ?? $input['id'] ?? 0);
       $userId = (int) (Session::getLoginUserID() ?: 0);
       $origin = Session::isCron() ? 'cron' : ($userId > 0 ? 'human' : 'backend');
       $impersonatorId = null;
      if (method_exists(Session::class, 'getImpersonatorId')) {
         try {
            $value = Session::getImpersonatorId();
            $impersonatorId = is_numeric($value) && (int) $value > 0 ? (int) $value : null;
         } catch (\Throwable) {
            $impersonatorId = null;
         }
      }

      if ($profileId <= 0 || $entityId < 0) {
          return null;
      }

       return new AuthorizationContext($profileId, $entityId, $ticketId, $userId, $impersonatorId, $origin, (int) ($_SESSION['glpiactive_entity'] ?? $entityId));
   }
}
