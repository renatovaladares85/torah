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

      self::assertCount(19, $controls);
      self::assertSame([
          'opening_date', 'type', 'category', 'status', 'request_source', 'urgency', 'impact', 'priority', 'total_duration',
          'approval_request', 'requester', 'observer', 'assignee', 'associated_items', 'tto', 'ttr', 'internal_tto', 'internal_ttr', 'linked_tickets',
      ], array_column($controls, 'key'));
      foreach ($controls as $control) {
         self::assertIsString($control['key']);
         self::assertNotSame('', $control['key']);
         self::assertIsString($control['label']);
         self::assertNotSame('', $control['label']);
         self::assertIsArray($control['add_keys']);
         self::assertIsArray($control['update_keys']);
         self::assertIsBool($control['sensitive']);
         self::assertNotInstanceOf(TicketControlDefinition::class, $control);
      }
   }
}
