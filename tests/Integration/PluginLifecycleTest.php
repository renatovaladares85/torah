<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Integration;

use Config;
use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Infrastructure\Glpi\DatabaseInstaller;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('glpi-integration')]
final class PluginLifecycleTest extends TestCase
{
   protected function setUp(): void {
      if (!class_exists('Migration') || !isset($GLOBALS['DB'])) {
          self::markTestSkipped('A configured GLPI test instance is required.');
      }
   }

   public function testInstallStartsEmptyAndUninstallRemovesOnlyTorahData(): void {
       global $DB;

       self::assertTrue(DatabaseInstaller::install('0.2.0'));
       self::assertTrue($DB->tableExists(DatabaseInstaller::POLICY_SET_TABLE));
       self::assertTrue($DB->tableExists(DatabaseInstaller::BLOCKED_RULE_TABLE));
       self::assertTrue($DB->tableExists(DatabaseInstaller::POLICY_OPTION_TABLE));
       self::assertSame(
           ['profiles_id', 'entities_id'],
           $this->uniqueIndexColumns(DatabaseInstaller::POLICY_SET_TABLE, 'unicity'),
       );
       self::assertSame(0, countElementsInTable(DatabaseInstaller::POLICY_SET_TABLE));
       self::assertSame(0, countElementsInTable(DatabaseInstaller::BLOCKED_RULE_TABLE));
       self::assertSame(0, countElementsInTable(DatabaseInstaller::POLICY_OPTION_TABLE));

       Config::setConfigurationValues('plugin:torah', [
           'release_test_key' => 'release-test-value',
       ]);
       Config::setConfigurationValues('plugin:release-test-control', [
           'control_key' => 'control-value',
       ]);
       self::assertSame(
           'release-test-value',
           Config::getConfigurationValues('plugin:torah')['release_test_key'] ?? null,
       );

       self::assertTrue(DatabaseInstaller::uninstall());
       self::assertFalse($DB->tableExists(DatabaseInstaller::POLICY_SET_TABLE, false));
       self::assertFalse($DB->tableExists(DatabaseInstaller::BLOCKED_RULE_TABLE, false));
       self::assertFalse($DB->tableExists(DatabaseInstaller::POLICY_OPTION_TABLE, false));
       self::assertSame([], Config::getConfigurationValues('plugin:torah'));
       self::assertSame(
           ['control_key' => 'control-value'],
           Config::getConfigurationValues('plugin:release-test-control'),
       );

       Config::deleteConfigurationValues('plugin:release-test-control', ['control_key']);
   }

   public function testInstallRemovesDeprecatedRecipientRulesWithoutChangingOtherRules(): void {
      global $DB;

      self::assertTrue(DatabaseInstaller::install('0.4.10'));
      self::assertTrue($DB->insert(DatabaseInstaller::POLICY_SET_TABLE, [
          'profiles_id' => 999991,
          'entities_id' => 0,
          'is_recursive' => 0,
      ]));
      $policySetId = (int) $DB->insertId();
      foreach ([
          'ticket.field.location.update',
          'ticket.field.users_id_recipient',
          'ticket.field.users_id_recipient.add',
          'ticket.field.users_id_recipient.update',
          'ticket.field.contract.update',
      ] as $rule) {
         self::assertTrue($DB->insert(DatabaseInstaller::BLOCKED_RULE_TABLE, [
             'plugin_torah_policysets_id' => $policySetId,
             'rule_key' => $rule,
         ]));
      }
      self::assertTrue($DB->insert(DatabaseInstaller::POLICY_OPTION_TABLE, [
          'plugin_torah_policysets_id' => $policySetId,
          'option_key' => BackendRulePolicy::OPTION_KEY,
          'option_value' => '["ticket.field.location.update","ticket.field.users_id_recipient.update","ticket.field.contract.update"]',
      ]));

      self::assertTrue(DatabaseInstaller::install('0.4.11'));
      $rules = [];
      foreach ($DB->request([
          'SELECT' => ['rule_key'],
          'FROM' => DatabaseInstaller::BLOCKED_RULE_TABLE,
          'WHERE' => ['plugin_torah_policysets_id' => $policySetId],
          'ORDER' => ['rule_key ASC'],
      ]) as $row) {
         $rules[] = (string) $row['rule_key'];
      }
      self::assertSame(['ticket.field.contract.update', 'ticket.field.location.update'], $rules);

      $options = $DB->request([
          'SELECT' => ['option_value'],
          'FROM' => DatabaseInstaller::POLICY_OPTION_TABLE,
          'WHERE' => [
              'plugin_torah_policysets_id' => $policySetId,
              'option_key' => BackendRulePolicy::OPTION_KEY,
          ],
          'LIMIT' => 1,
      ]);
      self::assertSame(
         ['ticket.field.location.update', 'ticket.field.contract.update'],
         json_decode((string) $options->current()['option_value'], true, 32, JSON_THROW_ON_ERROR),
      );
      self::assertTrue(DatabaseInstaller::install('0.4.11'));
   }

   /** @return list<string> */
   private function uniqueIndexColumns(string $table, string $index): array {
       global $DB;

       /** @var array<int, string> $columns */
       $columns = [];
       $iterator = $DB->request(sprintf(
           'SHOW INDEX FROM `%s` WHERE `Key_name` = %s AND `Non_unique` = 0',
           $table,
           $DB->quoteValue($index),
       ));
      foreach ($iterator as $row) {
          $columns[(int) $row['Seq_in_index']] = (string) $row['Column_name'];
      }

       ksort($columns, SORT_NUMERIC);

       return array_values($columns);
   }
}
