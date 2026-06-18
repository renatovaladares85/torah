<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\CapabilityRegistry;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use PHPUnit\Framework\TestCase;

final class TicketFieldCatalogTest extends TestCase
{
   public function testTicketFieldRulesAreScopedToTicketActions(): void {
       $catalog = new PolicyCatalog(new CapabilityRegistry());
       $rule = $catalog->get('ticket.field.status.update');

       self::assertNotNull($rule);
       self::assertSame('properties', $rule->group);
       self::assertSame('assistance', $rule->domain);
       self::assertSame('ticket', $rule->object);
       self::assertSame('update', $rule->action);
       self::assertSame(['status'], $rule->inputKeys);
   }

   public function testFallbackCatalogContainsEditableTicketFields(): void {
       $fields = (new PolicyCatalog(new CapabilityRegistry()))->ticketFields();
       $keys = array_map(static fn ($field): string => $field->key, $fields);

       self::assertContains('status', $keys);
       self::assertContains('category', $keys);
       self::assertContains('sla_tto', $keys);
       self::assertContains('ola_ttr', $keys);
   }
}
