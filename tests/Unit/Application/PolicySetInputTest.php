<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use GlpiPlugin\Torah\Application\Admin\PolicySetInput;
use GlpiPlugin\Torah\Application\CapabilityRegistry;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use PHPUnit\Framework\TestCase;

final class PolicySetInputTest extends TestCase
{
   public function testNewRowStartsWithNoBlockedRules(): void {
       $input = PolicySetInput::fromHttp([
           'profiles_id' => '4',
           'entities_id' => '0',
       ], new PolicyCatalog(new CapabilityRegistry()));

       self::assertNull($input->id);
       self::assertFalse($input->recursive);
       self::assertSame([], $input->blockedRules);
       self::assertSame(
           json_encode(ActorItemtypePolicy::ITEMTYPES, JSON_THROW_ON_ERROR),
           $input->options[ActorItemtypePolicy::optionKey('requester')],
       );
   }

   public function testOnlyCatalogRulesAreAccepted(): void {
       $this->expectException(\InvalidArgumentException::class);

       PolicySetInput::fromHttp([
           'profiles_id'  => '4',
           'entities_id'  => '0',
           'blocked_rules' => ['ticket.field.unknown'],
       ], new PolicyCatalog(new CapabilityRegistry()));
   }

   public function testActorItemtypesMustNotBeEmptyWhenRoleWasSubmitted(): void {
       $this->expectException(\InvalidArgumentException::class);

       PolicySetInput::fromHttp([
           'profiles_id'              => '4',
           'entities_id'              => '0',
           'actor_itemtypes_present'  => ['requester' => '1'],
           'actor_itemtypes'          => ['observer' => ['User']],
       ], new PolicyCatalog(new CapabilityRegistry()));
   }

   public function testActorItemtypesArePersistedPerRole(): void {
       $input = PolicySetInput::fromHttp([
           'profiles_id'             => '4',
           'entities_id'             => '0',
           'actor_itemtypes_present' => [
               'requester' => '1',
               'observer'  => '1',
               'assign'    => '1',
           ],
           'actor_itemtypes'         => [
               'requester' => ['User', 'Supplier'],
               'observer'  => ['Group'],
               'assign'    => ['Supplier'],
           ],
       ], new PolicyCatalog(new CapabilityRegistry()));

       self::assertSame('["User","Supplier"]', $input->options[ActorItemtypePolicy::optionKey('requester')]);
       self::assertSame('["Group"]', $input->options[ActorItemtypePolicy::optionKey('observer')]);
       self::assertSame('["Supplier"]', $input->options[ActorItemtypePolicy::optionKey('assign')]);
   }
}
