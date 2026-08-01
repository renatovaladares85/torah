<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use GlpiPlugin\Torah\Application\TicketControlDefinition;
use GlpiPlugin\Torah\Infrastructure\Glpi\AdminPolicyModelBuilder;
use PHPUnit\Framework\TestCase;

final class AdminPolicyModelBuilderTest extends TestCase
{
   public function testBuildsTwigSafeModelForAllTicketControls(): void {
      $model = (new AdminPolicyModelBuilder())->build();
      $controls = $model['field_rules'];

      self::assertCount(21, $controls);
      self::assertSame([
          'opening_date', 'type', 'category', 'status', 'request_source', 'urgency', 'impact', 'priority', 'total_duration',
          'approval_request', 'requester', 'observer', 'assignee', 'associated_items', 'tto', 'ttr', 'internal_tto', 'internal_ttr', 'linked_tickets', 'solution_date', 'closing_date',
      ], array_column($controls, 'key'));
      foreach ($controls as $control) {
         self::assertIsString($control['key']);
         self::assertNotSame('', $control['key']);
         self::assertIsString($control['label']);
         self::assertNotSame('', $control['label']);
         self::assertIsArray($control['add_keys']);
         self::assertIsArray($control['update_keys']);
         self::assertIsBool($control['opening_applicable']);
         self::assertIsBool($control['update_applicable']);
         self::assertIsBool($control['sensitive']);
         self::assertNotInstanceOf(TicketControlDefinition::class, $control);
      }
      $resolution = $controls[array_search('solution_date', array_column($controls, 'key'), true)];
      self::assertFalse($resolution['opening_applicable']);
      self::assertTrue($resolution['update_applicable']);
   }
}
