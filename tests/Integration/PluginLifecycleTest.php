<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Integration;

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

   public function testInstallStartsEmptyAndUninstallRemovesOnlyPluginTables(): void {
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

       self::assertTrue(DatabaseInstaller::install('0.2.0'));
       self::assertTrue(DatabaseInstaller::uninstall());
       self::assertFalse($DB->tableExists(DatabaseInstaller::POLICY_SET_TABLE, false));
       self::assertFalse($DB->tableExists(DatabaseInstaller::BLOCKED_RULE_TABLE, false));
       self::assertFalse($DB->tableExists(DatabaseInstaller::POLICY_OPTION_TABLE, false));
   }

   /** @return list<string> */
   private function uniqueIndexColumns(string $table, string $index): array {
       global $DB;

       $columns = [];
       $iterator = $DB->request(sprintf(
           'SHOW INDEX FROM `%s` WHERE `Key_name` = %s AND `Non_unique` = 0 ORDER BY `Seq_in_index` ASC',
           $table,
           $DB->quoteValue($index),
       ));
      foreach ($iterator as $row) {
          $columns[] = (string) $row['Column_name'];
      }

       return $columns;
   }
}
