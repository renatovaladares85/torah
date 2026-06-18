<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Domain\Policy\PolicySet;

final class GlpiPolicyStore
{
    /** @return list<PolicySet> */
   public function all(): array {
       global $DB;

       $sets = [];
       $iterator = $DB->request([
           'FROM'  => DatabaseInstaller::POLICY_SET_TABLE,
           'ORDER' => ['profiles_id ASC', 'entities_id ASC', 'is_recursive ASC'],
       ]);
      foreach ($iterator as $row) {
          $sets[] = $this->hydrate($row);
      }

       return $sets;
   }

   public function find(int $id): ?PolicySet {
       global $DB;

       $iterator = $DB->request([
           'FROM'  => DatabaseInstaller::POLICY_SET_TABLE,
           'WHERE' => ['id' => $id],
           'LIMIT' => 1,
       ]);

       return count($iterator) === 1 ? $this->hydrate($iterator->current()) : null;
   }

    /**
     * @param list<string> $blockedRules
     * @param array<string, string> $options
     */
   public function save(
        ?int $id,
        int $profileId,
        int $entityId,
        bool $recursive,
        array $blockedRules,
        array $options = [],
    ): int {
       global $DB;

       $this->assertNoDuplicate($id, $profileId, $entityId);
       $now = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');

       $DB->beginTransaction();
      try {
         if ($id === null) {
            if (!$DB->insert(DatabaseInstaller::POLICY_SET_TABLE, [
                'profiles_id'  => $profileId,
                'entities_id'  => $entityId,
                'is_recursive' => (int) $recursive,
                'date_creation' => $now,
                'date_mod'      => $now,
            ])) {
                throw new \RuntimeException('Unable to create the policy set.');
            }
            $id = (int) $DB->insertId();
         } else if (!$DB->update(
              DatabaseInstaller::POLICY_SET_TABLE,
              [
                  'profiles_id'  => $profileId,
                  'entities_id'  => $entityId,
                  'is_recursive' => (int) $recursive,
                  'date_mod'      => $now,
              ],
              ['id' => $id],
          )) {
             throw new \RuntimeException('Unable to update the policy set.');
         }

         if (!$DB->delete(DatabaseInstaller::BLOCKED_RULE_TABLE, ['plugin_torah_policysets_id' => $id])) {
             throw new \RuntimeException('Unable to replace the blocked rules.');
         }

         foreach (array_values(array_unique($blockedRules)) as $ruleKey) {
            if (!$DB->insert(DatabaseInstaller::BLOCKED_RULE_TABLE, [
                 'plugin_torah_policysets_id' => $id,
                 'rule_key'                   => $ruleKey,
                 'date_creation'              => $now,
                 'date_mod'                   => $now,
             ])) {
                throw new \RuntimeException('Unable to store a blocked rule.');
            }
         }

         if (!$DB->delete(DatabaseInstaller::POLICY_OPTION_TABLE, ['plugin_torah_policysets_id' => $id])) {
             throw new \RuntimeException('Unable to replace the policy options.');
         }

         foreach ($options as $key => $value) {
            if (!$DB->insert(DatabaseInstaller::POLICY_OPTION_TABLE, [
                 'plugin_torah_policysets_id' => $id,
                 'option_key'                 => $key,
                 'option_value'               => $value,
                 'date_creation'              => $now,
                 'date_mod'                   => $now,
             ])) {
                throw new \RuntimeException('Unable to store a policy option.');
            }
         }

          $DB->commit();

          return $id;
      } catch (\Throwable $error) {
          $DB->rollBack();
          throw $error;
      }
   }

   public function delete(int $id): void {
       global $DB;

       $DB->beginTransaction();
      try {
         if (!$DB->delete(DatabaseInstaller::BLOCKED_RULE_TABLE, ['plugin_torah_policysets_id' => $id])) {
            throw new \RuntimeException('Unable to remove policy rules.');
         }
         if (!$DB->delete(DatabaseInstaller::POLICY_OPTION_TABLE, ['plugin_torah_policysets_id' => $id])) {
             throw new \RuntimeException('Unable to remove policy options.');
         }
         if (!$DB->delete(DatabaseInstaller::POLICY_SET_TABLE, ['id' => $id])) {
             throw new \RuntimeException('Unable to remove the policy set.');
         }
          $DB->commit();
      } catch (\Throwable $error) {
          $DB->rollBack();
          throw $error;
      }
   }

   private function assertNoDuplicate(?int $id, int $profileId, int $entityId): void {
       global $DB;

       $iterator = $DB->request([
           'SELECT' => ['id'],
           'FROM'   => DatabaseInstaller::POLICY_SET_TABLE,
           'WHERE'  => [
               'profiles_id' => $profileId,
               'entities_id' => $entityId,
           ],
       ]);
      foreach ($iterator as $row) {
         if ($id === null || (int) $row['id'] !== $id) {
            throw new \RuntimeException('A policy set already exists for this profile and entity.');
         }
      }
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
