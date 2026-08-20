<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Application\CapabilityRegistry;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;
use PHPUnit\Framework\TestCase;

final class BackendRulePolicyTest extends TestCase
{
   public function testMissingOptionPreservesLegacyBackendEnforcement(): void {
      $set = new PolicySet(1, 2, 0, false, ['ticket.field.status.update']);
      self::assertTrue(BackendRulePolicy::enforces($set, 'ticket.field.status.update', $this->catalog()));
   }

   public function testExplicitEmptyOptionDisablesBackendEnforcement(): void {
      $set = new PolicySet(1, 2, 0, false, ['ticket.field.status.update'], [BackendRulePolicy::OPTION_KEY => '[]']);
      self::assertFalse(BackendRulePolicy::enforces($set, 'ticket.field.status.update', $this->catalog()));
   }

   public function testExplicitRuleEnablesOnlySelectedRule(): void {
      $set = new PolicySet(1, 2, 0, false, ['ticket.field.status.update', 'ticket.field.type.update'], [BackendRulePolicy::OPTION_KEY => '["ticket.field.status.update"]']);
      self::assertTrue(BackendRulePolicy::enforces($set, 'ticket.field.status.update', $this->catalog()));
      self::assertFalse(BackendRulePolicy::enforces($set, 'ticket.field.type.update', $this->catalog()));
   }

   public function testRemainingBackendRulesDecodeAfterRecipientRuleRemoval(): void {
      $catalog = $this->catalog();

      self::assertSame(
         ['ticket.field.location.update', 'ticket.field.contract.update'],
         BackendRulePolicy::decode('["ticket.field.location.update","ticket.field.contract.update"]', $catalog),
      );
   }

   private function catalog(): PolicyCatalog {
      return new PolicyCatalog(new CapabilityRegistry());
   }
}
