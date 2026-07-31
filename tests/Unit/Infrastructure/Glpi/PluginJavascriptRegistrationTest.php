<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use PHPUnit\Framework\TestCase;

final class PluginJavascriptRegistrationTest extends TestCase
{
   public function testAdminJavascriptIsLoadedLocallyWithoutUnsupportedHookConstants(): void {
      $root = dirname(__DIR__, 4);
      $setup = (string) file_get_contents($root . '/setup.php');
      $adminPage = (string) file_get_contents($root . '/src/Infrastructure/Glpi/AdminPage.php');
      $script = 'plugins/torah/js/admin-policy-matrix.js';

      self::assertFileExists($root . '/js/admin-policy-matrix.js');
      self::assertStringContainsString("Html::script('{$script}'", $adminPage);
      self::assertStringNotContainsString('Html::requireJs(', $adminPage);
      self::assertStringNotContainsString("'/plugins/torah/", $adminPage);
      self::assertStringNotContainsString('Hooks::JAVASCRIPT', $setup);
      self::assertStringNotContainsString('Hooks::ADD_JAVASCRIPT_MODULE', $setup);
      preg_match_all('/Hooks::([A-Z_]+)/', $setup, $matches);
      self::assertSame([
          'CSRF_COMPLIANT', 'POST_ITEM_FORM', 'FILTER_ACTORS', 'PRE_ITEM_UPDATE',
          'PRE_ITEM_ADD', 'ITEM_ADD', 'PRE_ITEM_DELETE', 'PRE_ITEM_PURGE',
      ], array_values(array_unique($matches[1])));
   }
}
