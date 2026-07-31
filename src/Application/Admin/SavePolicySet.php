<?php

namespace GlpiPlugin\Torah\Application\Admin;

use Entity;
use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use GlpiPlugin\Torah\Application\TicketControlCatalog;
use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiPolicyStore;
use Profile;
use Session;

final class SavePolicySet
{
   public function __construct(
       private readonly GlpiPolicyStore $store,
       private readonly PolicyCatalog $catalog,
   ) {
   }

   public function execute(PolicySetInput $input): int {
       $profile = new Profile();
       $entity = new Entity();
      if (!$profile->getFromDB($input->profileId) || !$entity->getFromDB($input->entityId)) {
          throw new \InvalidArgumentException('Profile or entity does not exist.');
      }

      if (!Session::haveAccessToEntity($input->entityId, $input->recursive)) {
          throw new \InvalidArgumentException('The active session cannot access this entity scope.');
      }

       $blockedRules = array_values(array_diff(
           $input->blockedRules,
           ['ticket.actor.requester', 'ticket.actor.observer', 'ticket.actor.assignee'],
       ));
      if ($input->id !== null) {
         $existing = $this->store->find($input->id);
         if ($existing === null) {
             throw new \InvalidArgumentException('Policy set does not exist.');
         }
         if (!Session::haveAccessToEntity($existing->entityId, $existing->recursive)) {
            throw new \InvalidArgumentException('The active session cannot access the existing policy scope.');
         }

         foreach ($existing->blockedRuleKeys() as $ruleKey) {
            if (in_array($ruleKey, ['ticket.actor.requester', 'ticket.actor.observer', 'ticket.actor.assignee'], true)) {
               continue;
            }
            if (!$this->catalog->has($ruleKey)) {
               $blockedRules[] = $ruleKey;
            }
         }
      }

       $options = $input->options;
      if ($input->id !== null) {
         foreach ($existing->options() as $key => $value) {
            if (!ActorItemtypePolicy::isOptionKey($key)) {
                $options[$key] = $value;
            }
         }
         if (isset($input->options[BackendRulePolicy::OPTION_KEY])) {
            $matrixRules = array_merge(
                (new TicketControlCatalog())->expand(array_map(static fn ($control): string => $control->key, (new TicketControlCatalog())->all()), 'add'),
                (new TicketControlCatalog())->expand(array_map(static fn ($control): string => $control->key, (new TicketControlCatalog())->all()), 'update'),
            );
            $preservedBackend = array_diff(
                BackendRulePolicy::decode($existing->option(BackendRulePolicy::OPTION_KEY), $this->catalog),
                $matrixRules,
            );
            $selectedBackend = BackendRulePolicy::decode($input->options[BackendRulePolicy::OPTION_KEY], $this->catalog);
            $options[BackendRulePolicy::OPTION_KEY] = BackendRulePolicy::encode([...$preservedBackend, ...$selectedBackend]);
         }
      }

       return $this->store->save(
           $input->id,
           $input->profileId,
           $input->entityId,
           $input->recursive,
           array_values(array_unique($blockedRules)),
           $options,
       );
   }
}
