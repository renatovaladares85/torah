<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Domain\Policy\PolicyRepository;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;

final class GlpiPolicyRepository implements PolicyRepository
{
   public function findExact(int $profileId, int $entityId): ?PolicySet {
       global $DB;

       $iterator = $DB->request([
           'FROM'   => DatabaseInstaller::POLICY_SET_TABLE,
           'WHERE'  => [
               'profiles_id' => $profileId,
               'entities_id' => $entityId,
           ],
           'ORDER'  => ['is_recursive ASC'],
           'LIMIT'  => 1,
       ]);

      if (count($iterator) === 0) {
          return null;
      }

       return $this->hydrate($iterator->current());
   }

   public function findNearestRecursive(int $profileId, array $ancestorEntityIds): ?PolicySet {
       global $DB;

      foreach ($ancestorEntityIds as $entityId) {
          $iterator = $DB->request([
              'FROM'   => DatabaseInstaller::POLICY_SET_TABLE,
              'WHERE'  => [
                  'profiles_id' => $profileId,
                  'entities_id' => $entityId,
                  'is_recursive' => 1,
              ],
              'LIMIT'  => 1,
          ]);

         if (count($iterator) > 0) {
            return $this->hydrate($iterator->current());
         }
      }

       return null;
   }

    /** @param array<string, mixed> $row */
   private function hydrate(array $row): PolicySet {
       global $DB;

       $rules = [];
       $iterator = $DB->request([
           'SELECT' => ['rule_key'],
           'FROM'   => DatabaseInstaller::BLOCKED_RULE_TABLE,
           'WHERE'  => ['plugin_torah_policysets_id' => (int) $row['id']],
           'ORDER'  => ['rule_key ASC'],
       ]);
      foreach ($iterator as $rule) {
         $rules[] = (string) $rule['rule_key'];
      }

      $options = [];
      $iterator = $DB->request([
           'SELECT' => ['option_key', 'option_value'],
           'FROM'   => DatabaseInstaller::POLICY_OPTION_TABLE,
           'WHERE'  => ['plugin_torah_policysets_id' => (int) $row['id']],
           'ORDER'  => ['option_key ASC'],
       ]);
      foreach ($iterator as $option) {
         $options[(string) $option['option_key']] = (string) $option['option_value'];
      }

       return new PolicySet(
           (int) $row['id'],
           (int) $row['profiles_id'],
           (int) $row['entities_id'],
           (bool) $row['is_recursive'],
           $rules,
           $options,
       );
   }
}
