<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Infrastructure\Glpi\AuthorizationContextFactory;
use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiGlobalActorSettingsStore;
use Ticket;

final class TicketPolicyPayload
{
   public function __construct(
      private readonly PolicyResolver $resolver,
      private readonly PolicyCatalog $catalog,
      private readonly AuthorizationContextFactory $contextFactory,
      private readonly GlpiGlobalActorSettingsStore $globalActorSettings,
   ) {
   }

   /**
    * @param array<string, mixed> $input
    * @return array<string, mixed>
    */
   public function forTicket(Ticket $ticket, array $input, string $action): array {
      $context = $this->contextFactory->fromTicketInput($ticket, $input);
      $actorItemtypes = $this->globalActorSettings->all();
      if ($context === null || !in_array($action, ['add', 'update'], true)) {
         return ['rules' => [], 'actor_itemtypes' => $actorItemtypes];
      }
      $policy = $this->resolver->resolve($context);
      if ($policy === null) {
         return ['rules' => [], 'actor_itemtypes' => $actorItemtypes];
      }

      $rules = [];
      foreach ($policy->blockedRuleKeys() as $ruleKey) {
         $rule = $this->catalog->get($ruleKey);
         if ($rule !== null && $rule->selectors !== [] && ($rule->action === null || $rule->action === $action)) {
            $rules[] = ['key' => $rule->key, 'selectors' => $rule->selectors];
         }
      }
      return [
          'rules' => $rules,
          'actor_itemtypes' => $actorItemtypes,
          'message' => __('Blocked by the active Torah policy.', 'torah'),
      ];
   }
}
