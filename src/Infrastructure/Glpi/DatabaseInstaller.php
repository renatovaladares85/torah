<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use DBConnection;
use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Application\GlobalActorItemtypePolicy;
use Migration;

final class DatabaseInstaller
{
   public const POLICY_SET_TABLE = 'glpi_plugin_torah_policysets';
   public const BLOCKED_RULE_TABLE = 'glpi_plugin_torah_blockedrules';
   public const POLICY_OPTION_TABLE = 'glpi_plugin_torah_policyoptions';

   /** @var list<string> */
   private const REMOVED_RECIPIENT_RULE_KEYS = [
       'ticket.field.users_id_recipient',
       'ticket.field.users_id_recipient.add',
       'ticket.field.users_id_recipient.update',
   ];

   public static function install(string $version): bool {
       global $DB;

       $migration = new Migration($version);
       $charset = DBConnection::getDefaultCharset();
       $collation = DBConnection::getDefaultCollation();
       $keySign = DBConnection::getDefaultPrimaryKeySignOption();

      if (!$DB->tableExists(self::POLICY_SET_TABLE)) {
          $table = self::POLICY_SET_TABLE;
          $query = "CREATE TABLE `$table` (
                `id` INT {$keySign} NOT NULL AUTO_INCREMENT,
                `profiles_id` INT {$keySign} NOT NULL,
                `entities_id` INT {$keySign} NOT NULL,
                `is_recursive` TINYINT NOT NULL DEFAULT 0,
                `date_creation` TIMESTAMP NULL DEFAULT NULL,
                `date_mod` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`profiles_id`, `entities_id`),
                KEY `profiles_id` (`profiles_id`),
                KEY `entities_id` (`entities_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC";
          $DB->queryOrDie($query, 'Unable to create Torah policy sets table.');
      } else {
          $table = self::POLICY_SET_TABLE;
          $query = "ALTER TABLE `$table`
                DROP INDEX `unicity`,
                ADD UNIQUE KEY `unicity` (`profiles_id`, `entities_id`)";
          $DB->queryOrDie($query, 'Unable to align Torah policy set uniqueness.');
      }

      if (!$DB->tableExists(self::BLOCKED_RULE_TABLE)) {
          $table = self::BLOCKED_RULE_TABLE;
          $query = "CREATE TABLE `$table` (
                `id` INT {$keySign} NOT NULL AUTO_INCREMENT,
                `plugin_torah_policysets_id` INT {$keySign} NOT NULL,
                `rule_key` VARCHAR(191) NOT NULL,
                `date_creation` TIMESTAMP NULL DEFAULT NULL,
                `date_mod` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`plugin_torah_policysets_id`, `rule_key`),
                KEY `plugin_torah_policysets_id` (`plugin_torah_policysets_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC";
          $DB->queryOrDie($query, 'Unable to create Torah blocked rules table.');
      }

      if (!$DB->tableExists(self::POLICY_OPTION_TABLE)) {
          $table = self::POLICY_OPTION_TABLE;
          $query = "CREATE TABLE `$table` (
                `id` INT {$keySign} NOT NULL AUTO_INCREMENT,
                `plugin_torah_policysets_id` INT {$keySign} NOT NULL,
                `option_key` VARCHAR(191) NOT NULL,
                `option_value` TEXT NOT NULL,
                `date_creation` TIMESTAMP NULL DEFAULT NULL,
                `date_mod` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`plugin_torah_policysets_id`, `option_key`),
                KEY `plugin_torah_policysets_id` (`plugin_torah_policysets_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC";
          $DB->queryOrDie($query, 'Unable to create Torah policy options table.');
      }

       self::migrateLegacyFieldRules();
       self::migrateLegacyActorRules();
       self::migrateLegacyActorItemtypes();
       self::removeDeprecatedRecipientRules();

       $migration->executeMigration();

       return true;
   }

   public static function uninstall(): bool {
       global $DB;

      (new GlpiGlobalActorSettingsStore())->clear();

      foreach ([self::POLICY_OPTION_TABLE, self::BLOCKED_RULE_TABLE, self::POLICY_SET_TABLE] as $table) {
         if ($DB->tableExists($table)) {
            $DB->queryOrDie("DROP TABLE `$table`", sprintf('Unable to remove %s.', $table));
         }
      }

       return true;
   }

   private static function migrateLegacyFieldRules(): void {
       global $DB;

       $now = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
       $table = self::BLOCKED_RULE_TABLE;
       $iterator = $DB->request("SELECT `id`, `plugin_torah_policysets_id`, `rule_key` FROM `$table` WHERE `rule_key` LIKE 'ticket.field.%'");
      foreach ($iterator as $row) {
          $legacyKey = (string) $row['rule_key'];
         if (str_ends_with($legacyKey, '.add') || str_ends_with($legacyKey, '.update')) {
             continue;
         }

          $policySetId = (int) $row['plugin_torah_policysets_id'];
         foreach (['add', 'update'] as $action) {
             $newKey = sprintf('%s.%s', $legacyKey, $action);
             $existing = $DB->request([
                 'FROM'   => self::BLOCKED_RULE_TABLE,
                 'WHERE'  => [
                     'plugin_torah_policysets_id' => $policySetId,
                     'rule_key'                   => $newKey,
                 ],
                 'LIMIT'  => 1,
             ]);
            if (count($existing) > 0) {
                continue;
            }

             $DB->insert(self::BLOCKED_RULE_TABLE, [
                 'plugin_torah_policysets_id' => $policySetId,
                 'rule_key'                   => $newKey,
                 'date_creation'              => $now,
                 'date_mod'                   => $now,
             ]);
         }

          $DB->delete(self::BLOCKED_RULE_TABLE, ['id' => (int) $row['id']]);
      }
   }

   private static function migrateLegacyActorRules(): void {
      global $DB;

      $now = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
      $table = self::BLOCKED_RULE_TABLE;
      foreach ($DB->request("SELECT `id`, `plugin_torah_policysets_id`, `rule_key` FROM `$table` WHERE `rule_key` IN ('ticket.actor.requester', 'ticket.actor.observer', 'ticket.actor.assignee')") as $row) {
         $legacy = (string) $row['rule_key'];
         $policySetId = (int) $row['plugin_torah_policysets_id'];
         foreach (['add', 'update'] as $action) {
            $rule = "{$legacy}.{$action}";
            $existing = $DB->request([
                'FROM' => self::BLOCKED_RULE_TABLE,
                'WHERE' => ['plugin_torah_policysets_id' => $policySetId, 'rule_key' => $rule],
                'LIMIT' => 1,
            ]);
            if (count($existing) === 0) {
               $DB->insert(self::BLOCKED_RULE_TABLE, [
                   'plugin_torah_policysets_id' => $policySetId,
                   'rule_key' => $rule,
                   'date_creation' => $now,
                   'date_mod' => $now,
               ]);
            }
         }
         $DB->delete(self::BLOCKED_RULE_TABLE, ['id' => (int) $row['id']]);
      }
   }

   private static function removeDeprecatedRecipientRules(): void {
       global $DB;

       $DB->delete(self::BLOCKED_RULE_TABLE, ['rule_key' => self::REMOVED_RECIPIENT_RULE_KEYS]);

       $now = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
      foreach ($DB->request([
           'SELECT' => ['id', 'option_value'],
           'FROM'   => self::POLICY_OPTION_TABLE,
           'WHERE'  => ['option_key' => BackendRulePolicy::OPTION_KEY],
       ]) as $option) {
         try {
            $keys = json_decode((string) $option['option_value'], true, 32, JSON_THROW_ON_ERROR);
         } catch (\JsonException) {
            continue;
         }
         if (!is_array($keys)) {
            continue;
         }

         $filtered = array_values(array_filter(
             $keys,
             static fn (mixed $key): bool => !is_string($key) || !in_array($key, self::REMOVED_RECIPIENT_RULE_KEYS, true),
         ));
         if ($filtered === $keys) {
            continue;
         }
         $DB->update(self::POLICY_OPTION_TABLE, [
             'option_value' => json_encode($filtered, JSON_THROW_ON_ERROR),
             'date_mod'     => $now,
         ], ['id' => (int) $option['id']]);
      }
   }

   private static function migrateLegacyActorItemtypes(): void {
      global $DB;

      $settings = new GlpiGlobalActorSettingsStore();
      $config = $settings->raw();
      if (!empty($config[GlobalActorItemtypePolicy::MIGRATION_KEY])) {
         return;
      }

      $legacyKeys = [];
      foreach (array_keys(GlobalActorItemtypePolicy::roleLabels()) as $role) {
         $legacyKeys[] = GlobalActorItemtypePolicy::legacyOptionKey($role);
      }
      $rows = [];
      foreach ($DB->request([
          'SELECT' => ['plugin_torah_policysets_id', 'option_key', 'option_value'],
          'FROM' => self::POLICY_OPTION_TABLE,
          'WHERE' => ['option_key' => $legacyKeys],
      ]) as $row) {
         $rows[] = $row;
      }

      $now = $_SESSION['glpi_currenttime'] ?? date('c');
      $DB->beginTransaction();
      try {
         if (!$settings->hasActorConfiguration()) {
            $settings->save(GlobalActorItemtypePolicy::defaults());
         }

         if ($rows !== [] && !array_key_exists(GlobalActorItemtypePolicy::BACKUP_KEY, $config)) {
            $backup = [];
            foreach ($rows as $row) {
               $policySetId = (int) $row['plugin_torah_policysets_id'];
               $policy = $DB->request([
                   'SELECT' => ['profiles_id', 'entities_id'],
                   'FROM' => self::POLICY_SET_TABLE,
                   'WHERE' => ['id' => $policySetId],
                   'LIMIT' => 1,
               ]);
               $scope = count($policy) === 1 ? $policy->current() : [];
               $backup[] = [
                   'policy_set_id' => $policySetId,
                   'profile_id' => (int) ($scope['profiles_id'] ?? 0),
                   'entity_id' => (int) ($scope['entities_id'] ?? 0),
                   'option_key' => (string) $row['option_key'],
                   'option_value' => (string) $row['option_value'],
                   'migrated_at' => $now,
                   'source_version' => 'pre-0.4.0',
               ];
            }
            $settings->saveRaw([
                GlobalActorItemtypePolicy::BACKUP_KEY => json_encode($backup, JSON_THROW_ON_ERROR),
            ]);
         }

         foreach ($legacyKeys as $key) {
            if (!$DB->delete(self::POLICY_OPTION_TABLE, ['option_key' => $key])) {
               throw new \RuntimeException('Unable to remove legacy actor itemtype options.');
            }
         }
         $settings->saveRaw([GlobalActorItemtypePolicy::MIGRATION_KEY => '1']);
         $DB->commit();
      } catch (\Throwable $error) {
         $DB->rollBack();
         throw $error;
      }
   }
}
