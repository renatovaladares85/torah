<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Infrastructure\Glpi\AuthorizationContextFactory;
use Ticket;

final class TicketPolicyPayload
{
   public function __construct(
      private readonly PolicyResolver $resolver,
      private readonly PolicyCatalog $catalog,
      private readonly AuthorizationContextFactory $contextFactory,
   ) {
   }

   /** @param array<string, mixed> $input @return array<string, mixed> */
   public function forTicket(Ticket $ticket, array $input, string $action): array {
      $context = $this->contextFactory->fromTicketInput($ticket, $input);
      if ($context === null || !in_array($action, ['add', 'update'], true)) {
         return ['rules' => [], 'actor_itemtypes' => []];
      }
      $policy = $this->resolver->resolve($context);
      if ($policy === null) {
         return ['rules' => [], 'actor_itemtypes' => []];
      }

      $rules = [];
      foreach ($policy->blockedRuleKeys() as $ruleKey) {
         $rule = $this->catalog->get($ruleKey);
         if ($rule !== null && $rule->selectors !== [] && ($rule->action === null || $rule->action === $action)) {
            $rules[] = ['key' => $rule->key, 'selectors' => $rule->selectors];
         }
      }
      $actorItemtypes = [];
      foreach (array_keys(ActorItemtypePolicy::roleLabels()) as $role) {
         $actorItemtypes[$role] = ActorItemtypePolicy::allowedFor($policy, $role);
      }

      return [
          'rules' => $rules,
          'actor_itemtypes' => $actorItemtypes,
          'message' => __('Blocked by the active Torah policy.', 'torah'),
      ];
   }
}
