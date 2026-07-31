<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use PHPUnit\Framework\TestCase;

final class PluginJavascriptRegistrationTest extends TestCase
{
   public function testAdminJavascriptIsRegisteredAndLoadedByRelativeScriptPath(): void {
      $root = dirname(__DIR__, 4);
      $setup = (string) file_get_contents($root . '/setup.php');
      $adminPage = (string) file_get_contents($root . '/src/Infrastructure/Glpi/AdminPage.php');
      $script = 'plugins/torah/js/admin-policy-matrix.js';

      self::assertFileExists($root . '/js/admin-policy-matrix.js');
      self::assertStringContainsString("['torah_admin_policy_matrix'] = ['{$script}']", $setup);
      self::assertStringContainsString("Html::script('{$script}'", $adminPage);
      self::assertStringNotContainsString('Html::requireJs(', $adminPage);
      self::assertStringNotContainsString("'/plugins/torah/", $adminPage);
   }
}
