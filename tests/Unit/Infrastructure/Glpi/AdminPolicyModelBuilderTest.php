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

      self::assertSame([
          'title', 'description', 'entity', 'opening_date', 'type', 'category', 'status', 'request_source', 'urgency', 'impact', 'priority', 'location', 'contract', 'total_duration',
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
      self::assertNotContains('recipient', array_column($controls, 'key'));
      $resolution = $controls[array_search('solution_date', array_column($controls, 'key'), true)];
      self::assertFalse($resolution['opening_applicable']);
      self::assertTrue($resolution['update_applicable']);

      $itemtypes = $model['actor_itemtypes'];
      self::assertSame(['User', 'Group', 'Supplier'], array_column($itemtypes, 'key'));
      self::assertCount(3, $itemtypes);
      foreach ($itemtypes as $itemtype) {
         self::assertIsString($itemtype['label']);
         self::assertNotSame('', $itemtype['label']);
      }

      $roles = $model['actor_itemtype_roles'];
      self::assertSame(['requester', 'observer', 'assign'], array_column($roles, 'key'));
      foreach ($roles as $role) {
         self::assertSame($itemtypes, $role['itemtypes']);
      }
   }
}
