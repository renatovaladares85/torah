<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Authorization\AuthorizationDecision;
use GlpiPlugin\Torah\Infrastructure\Glpi\AuthorizationContextFactory;
use GlpiPlugin\Torah\Infrastructure\Glpi\ServiceFactory;
use Plugin;
use Ticket;

final class PolicyApi
{
   public static function decideForTicket(string $capabilityKey, int $ticketId): AuthorizationDecision {
      if (!Plugin::isPluginActive('torah') || !ServiceFactory::catalog()->has($capabilityKey)) {
          return AuthorizationDecision::allow();
      }

       $ticket = new Ticket();
      if (!$ticket->getFromDB($ticketId)) {
          return AuthorizationDecision::allow();
      }

       $context = (new AuthorizationContextFactory())->fromTicket($ticket);
      if ($context === null) {
          return AuthorizationDecision::allow();
      }

       return ServiceFactory::resolver()->decide($context, $capabilityKey);
   }
}
