<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Integration;

use Config;
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
