<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use PHPUnit\Framework\TestCase;

final class AdminPolicySimplificationTest extends TestCase
{
   public function testGlobalActorSettingsPrecedeTheNewPolicyForm(): void {
       $root = dirname(__DIR__, 4);
       $template = (string) file_get_contents($root . '/templates/admin/policies.html.twig');
       $matrix = (string) file_get_contents($root . '/templates/admin/rule_matrix.html.twig');

       self::assertLessThan(strpos($template, 'new_entity'), strpos($template, 'global_actor_settings'));
       self::assertStringNotContainsString('actor_itemtypes_present', $matrix);
       self::assertStringNotContainsString('name="actor_itemtypes[', $matrix);
       self::assertStringNotContainsString('data-torah-select-all', $template);
       self::assertStringNotContainsString('data-torah-clear-all', $template);
   }

   public function testMatrixHasScopedRowAndColumnHelpers(): void {
       $root = dirname(__DIR__, 4);
       $matrix = (string) file_get_contents($root . '/templates/admin/rule_matrix.html.twig');
       $script = (string) file_get_contents($root . '/js/admin-policy-matrix.js');

       self::assertStringContainsString('data-torah-row-all', $matrix);
       self::assertStringContainsString('data-torah-column-all', $matrix);
       self::assertStringContainsString('indeterminate', $script);
       self::assertStringContainsString('name="backend_controls_present"', $matrix);
       self::assertStringNotContainsString('backend.checked = true', $script);
       self::assertStringNotContainsString('data-torah-select-all', $script);
   }

   public function testProfilelessProcessesHaveNoBackendProfileFallback(): void {
       $root = dirname(__DIR__, 4);
       $factory = (string) file_get_contents($root . '/src/Infrastructure/Glpi/AuthorizationContextFactory.php');

       self::assertStringNotContainsString('BackendExecutionProfile', $factory);
       self::assertFileDoesNotExist($root . '/src/Infrastructure/Glpi/BackendExecutionProfile.php');
   }
}
