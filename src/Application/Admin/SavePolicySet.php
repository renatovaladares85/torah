<?php

namespace GlpiPlugin\Torah\Application\Admin;

use Entity;
use GlpiPlugin\Torah\Application\PolicyCatalog;
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

       $blockedRules = $input->blockedRules;
      if ($input->id !== null) {
         $existing = $this->store->find($input->id);
         if ($existing === null) {
             throw new \InvalidArgumentException('Policy set does not exist.');
         }
         if (!Session::haveAccessToEntity($existing->entityId, $existing->recursive)) {
            throw new \InvalidArgumentException('The active session cannot access the existing policy scope.');
         }

         foreach ($existing->blockedRuleKeys() as $ruleKey) {
            if (!$this->catalog->has($ruleKey)) {
               $blockedRules[] = $ruleKey;
            }
         }
      }

       return $this->store->save(
           $input->id,
           $input->profileId,
           $input->entityId,
           $input->recursive,
           array_values(array_unique($blockedRules)),
       );
   }
}
