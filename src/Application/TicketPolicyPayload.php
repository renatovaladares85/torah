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
      $added = [];
      foreach ($policy->blockedRuleKeys() as $ruleKey) {
         $control = $this->catalog->findControlByRuleKey($ruleKey);
         if ($control !== null) {
            $applicableRules = $action === 'add' ? $control->addRuleKeys : $control->updateRuleKeys;
            if (!in_array($ruleKey, $applicableRules, true) || isset($added[$control->key])) {
               continue;
            }
            $rules[] = [
                'key' => $control->key,
                'label' => $control->label,
                'strategy' => $control->lockStrategy,
                'selectors' => $control->selectors,
                'controls' => $control->controls,
            ];
            $added[$control->key] = true;
            continue;
         }

         $rule = $this->catalog->get($ruleKey);
         if ($rule !== null && $rule->selectors !== [] && ($rule->action === null || $rule->action === $action)) {
            $rules[] = [
                'key' => $rule->key,
                'label' => $rule->label,
                'strategy' => 'text',
                'selectors' => $rule->selectors,
                'controls' => [],
            ];
         }
      }
      return [
          'rules' => $rules,
          'actor_itemtypes' => $actorItemtypes,
          'message' => __('The field "%s" is read-only because it is restricted by the active Torah policy.', 'torah'),
      ];
   }
}
