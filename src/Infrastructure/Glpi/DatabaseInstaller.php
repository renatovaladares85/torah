<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use DBConnection;
use Migration;

final class DatabaseInstaller
{
   public const POLICY_SET_TABLE = 'glpi_plugin_torah_policysets';
   public const BLOCKED_RULE_TABLE = 'glpi_plugin_torah_blockedrules';

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
                UNIQUE KEY `unicity` (`profiles_id`, `entities_id`, `is_recursive`),
                KEY `profiles_id` (`profiles_id`),
                KEY `entities_id` (`entities_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC";
          $DB->queryOrDie($query, 'Unable to create Torah policy sets table.');
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

       $migration->executeMigration();

       return true;
   }

   public static function uninstall(): bool {
       global $DB;

      foreach ([self::BLOCKED_RULE_TABLE, self::POLICY_SET_TABLE] as $table) {
         if ($DB->tableExists($table)) {
            $DB->queryOrDie("DROP TABLE `$table`", sprintf('Unable to remove %s.', $table));
         }
      }

       return true;
   }
}
