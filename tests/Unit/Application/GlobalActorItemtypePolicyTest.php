<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\GlobalActorItemtypePolicy;
use PHPUnit\Framework\TestCase;

final class GlobalActorItemtypePolicyTest extends TestCase
{
   public function testDefaultsAllowEveryKnownTypeForEveryRole(): void {
       self::assertSame([
           'requester' => ['User', 'Group', 'Supplier'],
           'observer' => ['User', 'Group', 'Supplier'],
           'assign' => ['User', 'Group', 'Supplier'],
       ], GlobalActorItemtypePolicy::defaults());
   }

   public function testNormalizeDropsUnknownValuesAndDuplicates(): void {
      self::assertSame(['User', 'Supplier'], GlobalActorItemtypePolicy::normalize(['Supplier', 'Unknown', 'User', 'Supplier']));
   }

   public function testDecodeFallsBackToDefaultsForMalformedOrEmptyConfiguration(): void {
       self::assertSame(GlobalActorItemtypePolicy::ITEMTYPES, GlobalActorItemtypePolicy::decode('{'));
       self::assertSame(GlobalActorItemtypePolicy::ITEMTYPES, GlobalActorItemtypePolicy::decode('[]'));
       self::assertSame(['User'], GlobalActorItemtypePolicy::decode('["User"]'));
   }

   public function testEncodeRequiresAtLeastOneKnownType(): void {
       $this->expectException(\InvalidArgumentException::class);
       GlobalActorItemtypePolicy::encode(['Unknown']);
   }

   public function testLegacyKeysAreRecognizedWithoutBeingGlobalKeys(): void {
       self::assertTrue(GlobalActorItemtypePolicy::isLegacyOptionKey('ticket.actor.assignee.allowed_itemtypes'));
       self::assertFalse(GlobalActorItemtypePolicy::isLegacyOptionKey(GlobalActorItemtypePolicy::key('assign')));
   }
}
