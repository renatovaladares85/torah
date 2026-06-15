<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

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
   }

   public function testOnlyCatalogRulesAreAccepted(): void {
       $this->expectException(\InvalidArgumentException::class);

       PolicySetInput::fromHttp([
           'profiles_id'  => '4',
           'entities_id'  => '0',
           'blocked_rules' => ['ticket.field.unknown'],
       ], new PolicyCatalog(new CapabilityRegistry()));
   }
}
