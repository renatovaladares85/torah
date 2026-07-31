<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use PHPUnit\Framework\TestCase;

final class AdminPolicySimplificationTest extends TestCase
{
   public function testNewPolicyFormContainsOnlyEntityProfileRecursionAndAdd(): void {
      $root = dirname(__DIR__, 4);
      $template = (string) file_get_contents($root . '/templates/admin/policies.html.twig');

      self::assertLessThan(strpos($template, 'new_profile'), strpos($template, 'new_entity'));
      self::assertStringNotContainsString('backend_profile', $template);
      self::assertStringNotContainsString("@torah/admin/rule_matrix.html.twig' with {\n            'policy_model': policy_model,\n            'blocked_rules': {}", $template);
      self::assertStringContainsString('name="save" value="1">{{ _x(\'button\', \'Add\') }}', $template);
   }

   public function testExistingPolicyActionsAreScopedToItsForm(): void {
      $root = dirname(__DIR__, 4);
      $template = (string) file_get_contents($root . '/templates/admin/policies.html.twig');
      $script = (string) file_get_contents($root . '/js/admin-policy-matrix.js');

      self::assertStringContainsString('data-torah-select-all', $template);
      self::assertStringContainsString('data-torah-clear-all', $template);
      self::assertStringContainsString('Remove this entity configuration?', $template);
      self::assertStringContainsString("form.querySelector('[data-torah-select-all]')", $script);
      self::assertStringContainsString("form.querySelector('[data-torah-clear-all]')", $script);
   }

   public function testProfilelessProcessesHaveNoBackendProfileFallback(): void {
      $root = dirname(__DIR__, 4);
      $factory = (string) file_get_contents($root . '/src/Infrastructure/Glpi/AuthorizationContextFactory.php');

      self::assertStringNotContainsString('BackendExecutionProfile', $factory);
      self::assertFileDoesNotExist($root . '/src/Infrastructure/Glpi/BackendExecutionProfile.php');
   }
}
