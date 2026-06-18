<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use Ticket;

final class TicketPolicyPresenter
{
    /** @param array<string, mixed> $params */
   public static function render(array $params): void {
       $ticket = $params['item'] ?? null;
      if (!$ticket instanceof Ticket) {
          return;
      }

       $contextFactory = new AuthorizationContextFactory();
       $context = $ticket->isNewItem()
           ? $contextFactory->fromTicketInput($ticket, ['entities_id' => (int) ($_SESSION['glpiactive_entity'] ?? 0)])
           : $contextFactory->fromTicket($ticket);
      if ($context === null) {
          return;
      }

       $policy = ServiceFactory::resolver()->resolve($context);
      if ($policy === null) {
          return;
      }

       $rules = [];
       $catalog = ServiceFactory::catalog();
       $action = $ticket->isNewItem() ? 'add' : 'update';
      foreach ($policy->blockedRuleKeys() as $ruleKey) {
          $rule = $catalog->get($ruleKey);
         if ($rule !== null && $rule->selectors !== [] && ($rule->action === null || $rule->action === $action)) {
             $rules[] = [
                 'key'       => $rule->key,
                 'selectors' => $rule->selectors,
             ];
         }
      }

       $actorItemtypes = [];
      foreach (array_keys(ActorItemtypePolicy::roleLabels()) as $role) {
          $actorItemtypes[$role] = ActorItemtypePolicy::allowedFor($policy, $role);
      }

       TemplateRenderer::getInstance()->display('@torah/ticket/policy_data.html.twig', [
           'payload' => [
               'rules'           => $rules,
               'actor_itemtypes' => $actorItemtypes,
               'message'         => __('Blocked by the active Torah policy.', 'torah'),
           ],
       ]);
   }
}
