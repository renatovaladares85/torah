<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Glpi\Application\View\TemplateRenderer;
use Ticket;

final class TicketPolicyPresenter
{
    /** @param array<string, mixed> $params */
   public static function render(array $params): void {
       $ticket = $params['item'] ?? null;
      if (!$ticket instanceof Ticket || $ticket->isNewItem()) {
          return;
      }

       $context = (new AuthorizationContextFactory())->fromTicket($ticket);
      if ($context === null) {
          return;
      }

       $policy = ServiceFactory::resolver()->resolve($context);
      if ($policy === null) {
          return;
      }

       $rules = [];
       $catalog = ServiceFactory::catalog();
      foreach ($policy->blockedRuleKeys() as $ruleKey) {
          $rule = $catalog->get($ruleKey);
         if ($rule !== null && $rule->selectors !== []) {
             $rules[] = [
                 'key'       => $rule->key,
                 'selectors' => $rule->selectors,
             ];
         }
      }

       TemplateRenderer::getInstance()->display('@torah/ticket/policy_data.html.twig', [
           'payload' => [
               'rules'   => $rules,
               'message' => __('Blocked by the active Torah policy.', 'torah'),
           ],
       ]);
   }
}
