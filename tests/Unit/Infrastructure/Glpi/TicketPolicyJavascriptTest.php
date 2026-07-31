<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use PHPUnit\Framework\TestCase;

final class TicketPolicyJavascriptTest extends TestCase
{
   public function testTicketPolicyJavascriptUsesTheExplicitTicketFormAndReportsFailures(): void {
      $root = dirname(__DIR__, 4);
      $script = (string) file_get_contents($root . '/js/ticket-policy.js');
      $template = (string) file_get_contents($root . '/templates/ticket/policy_data.html.twig');

      self::assertStringContainsString('form#itil-form', $template);
      self::assertStringContainsString("local?.matches('form#itil-form')", $script);
      self::assertStringNotContainsString("document.querySelector('form')", $script);
      self::assertStringContainsString('[Torah]', $script);
      self::assertStringContainsString('validPayload', $script);
      self::assertStringContainsString('MutationObserver', $script);
   }
}
