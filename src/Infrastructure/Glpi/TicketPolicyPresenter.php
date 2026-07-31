<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Glpi\Application\View\TemplateRenderer;
use Html;
use Plugin;
use Ticket;

final class TicketPolicyPresenter
{
    /** @param array<string, mixed> $params */
   public static function render(array $params): void {
       $ticket = $params['item'] ?? null;
      if (!$ticket instanceof Ticket) {
          return;
      }

       echo Html::script('plugins/torah/js/ticket-policy.js', ['version' => PLUGIN_TORAH_VERSION]);
       $action = $ticket->isNewItem() ? 'add' : 'update';
       $input = $ticket->isNewItem() ? ['entities_id' => (int) ($_SESSION['glpiactive_entity'] ?? 0)] : [];
       $payload = ServiceFactory::policyPayload()->forTicket($ticket, $input, $action);

       TemplateRenderer::getInstance()->display('@torah/ticket/policy_data.html.twig', [
           'payload' => $payload,
           'ticket_id' => (int) ($ticket->fields['id'] ?? 0),
           'entity_id' => (int) ($input['entities_id'] ?? $ticket->fields['entities_id'] ?? 0),
           'action' => $action,
           'url' => Plugin::getWebDir('torah') . '/ajax/ticket-policy.php',
       ]);
   }
}
