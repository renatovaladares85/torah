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
      self::assertStringContainsString('lockFlatpickr', $script);
      self::assertStringContainsString('instance.altInput', $script);
      self::assertStringContainsString("[data-toggle], [data-clear]", $script);
      self::assertStringContainsString('allowInput: false, clickOpens: false', $script);
      self::assertStringContainsString('restoreSnapshot', $script);
      self::assertStringContainsString("{ ...rule, strategy: 'text' }", $script);
      self::assertStringContainsString("data('select2')?.\$container?.[0]", $script);
      self::assertStringContainsString('select2:opening.torahPolicy', $script);
      self::assertStringContainsString('select2:selecting.torahPolicy', $script);
      self::assertStringNotContainsString(".next('.select2-container')", $script);
      self::assertStringContainsString("element.addEventListener('change', restoreValue, true)", $script);
      self::assertStringContainsString('restoreSelectValue(element, state)', $script);
      self::assertStringContainsString("element.matches('select[data-actor-type]')", $script);
      self::assertStringContainsString('lockSelect2(element, message)', $script);
      self::assertStringContainsString('applyContainer(container, false)', $script);
      self::assertStringContainsString('state.cleanup.forEach', $script);
      self::assertStringContainsString('const lockAction', $script);
      self::assertStringContainsString('const lockRichtext', $script);
      self::assertStringContainsString("rule.strategy === 'action'", $script);
      self::assertStringContainsString("rule.strategy === 'richtext'", $script);
      self::assertStringContainsString("editor.mode.set('readonly')", $script);
   }
}
