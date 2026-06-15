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

       self::assertTrue(DatabaseInstaller::install('0.1.0'));
       self::assertTrue($DB->tableExists(DatabaseInstaller::POLICY_SET_TABLE));
       self::assertTrue($DB->tableExists(DatabaseInstaller::BLOCKED_RULE_TABLE));
       self::assertSame(0, countElementsInTable(DatabaseInstaller::POLICY_SET_TABLE));
       self::assertSame(0, countElementsInTable(DatabaseInstaller::BLOCKED_RULE_TABLE));

       self::assertTrue(DatabaseInstaller::install('0.1.0'));
       self::assertTrue(DatabaseInstaller::uninstall());
       self::assertFalse($DB->tableExists(DatabaseInstaller::POLICY_SET_TABLE, false));
       self::assertFalse($DB->tableExists(DatabaseInstaller::BLOCKED_RULE_TABLE, false));
   }
}
