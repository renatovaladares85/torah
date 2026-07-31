<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use PHPUnit\Framework\TestCase;

final class CsrfPipelineRegressionTest extends TestCase
{
   public function testPostEndpointsRelyOnTheGlpiCsrfPipeline(): void {
      $root = dirname(__DIR__, 4);

      foreach (['front/policy.form.php', 'ajax/effective_policy.php'] as $endpoint) {
         $source = (string) file_get_contents($root . '/' . $endpoint);

         self::assertStringContainsString("include('../../../inc/includes.php');", $source, $endpoint);
         self::assertStringContainsString("['REQUEST_METHOD'] ?? '') !== 'POST'", $source, $endpoint);
         self::assertStringNotContainsString('Session::checkCSRF(', $source, $endpoint);
      }

      $policyEndpoint = (string) file_get_contents($root . '/front/policy.form.php');
      self::assertStringContainsString("Session::checkRight('config', UPDATE);", $policyEndpoint);
   }

   public function testEveryAdministrativePostFormHasExactlyOneGlpiCsrfToken(): void {
      $root = dirname(__DIR__, 4);
      $template = (string) file_get_contents($root . '/templates/admin/policies.html.twig');
      preg_match_all('/<form\\b[^>]*>.*?<\\/form>/is', $template, $matches);

      $postForms = array_filter(
         $matches[0],
         static fn (string $form): bool => preg_match('/\\bmethod\\s*=\\s*(["\\\'])post\\1/i', $form) === 1,
      );

      self::assertNotEmpty($postForms);
      foreach ($postForms as $form) {
         self::assertSame(
            1,
            preg_match_all('/<input\\b[^>]*\\bname\\s*=\\s*(["\\\'])_glpi_csrf_token\\1[^>]*>/i', $form),
         );
      }
   }

   public function testDeclaredMinimumGlpiVersionIsTenZeroTwenty(): void {
      $root = dirname(__DIR__, 4);

      self::assertStringContainsString("PLUGIN_TORAH_MIN_GLPI_VERSION', '10.0.20'", (string) file_get_contents($root . '/setup.php'));
      self::assertStringContainsString('<compatibility>~10.0.20</compatibility>', (string) file_get_contents($root . '/plugin.xml'));
      self::assertStringContainsString('>= 10.0.20 and < 10.0.99', (string) file_get_contents($root . '/README.md'));
   }
}
