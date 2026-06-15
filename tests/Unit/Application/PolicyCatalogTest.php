<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\CapabilityRegistry;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use PHPUnit\Framework\TestCase;

final class PolicyCatalogTest extends TestCase
{
   public function testBuiltInRulesAreStableAndUnique(): void {
       $catalog = new PolicyCatalog(new CapabilityRegistry());

       self::assertCount(22, $catalog->all());
       self::assertTrue($catalog->has('ticket.actor.requester'));
       self::assertTrue($catalog->has('ticket.field.time_to_own'));
   }

   public function testExternalCapabilityAppearsInSeparateGroup(): void {
       $registry = new CapabilityRegistry();
       $registry->register('ticket.custom_action', 'Custom action', 'provider');

       $rule = (new PolicyCatalog($registry))->get('ticket.custom_action');

       self::assertNotNull($rule);
       self::assertSame('external', $rule->group);
   }

   public function testDuplicateCapabilityIsRejected(): void {
       $registry = new CapabilityRegistry();
       $registry->register('ticket.custom_action', 'Custom action', 'provider');

       $this->expectException(\InvalidArgumentException::class);
       $registry->register('ticket.custom_action', 'Other action', 'provider');
   }
}
