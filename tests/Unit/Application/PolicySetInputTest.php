<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\Admin\PolicySetInput;
use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Application\CapabilityRegistry;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use PHPUnit\Framework\TestCase;

final class PolicySetInputTest extends TestCase
{
   public function testNewRowStartsWithNoBlockedRulesOrActorOptions(): void {
       $input = PolicySetInput::fromHttp([
           'profiles_id' => '4',
           'entities_id' => '0',
       ], new PolicyCatalog(new CapabilityRegistry()));

       self::assertNull($input->id);
       self::assertSame([], $input->blockedRules);
       self::assertSame([], $input->options);
   }

   public function testUnknownPolicyRuleIsRejected(): void {
       $this->expectException(\InvalidArgumentException::class);
       PolicySetInput::fromHttp([
           'profiles_id' => '4',
           'entities_id' => '0',
           'blocked_rules' => ['ticket.field.unknown'],
       ], new PolicyCatalog(new CapabilityRegistry()));
   }

   public function testLegacyActorInputsAreIgnored(): void {
       $input = PolicySetInput::fromHttp([
           'profiles_id' => '4',
           'entities_id' => '0',
           'actor_itemtypes_present' => ['requester' => '1'],
           'actor_itemtypes' => ['requester' => ['User']],
       ], new PolicyCatalog(new CapabilityRegistry()));

       self::assertSame([], $input->options);
   }

   public function testStructuredControlsExpandAndPersistBackendSelection(): void {
       $input = PolicySetInput::fromHttp([
           'profiles_id' => '4',
           'entities_id' => '0',
           'blocked_controls' => ['opening' => ['tto'], 'update' => ['requester']],
           'backend_controls' => ['tto'],
       ], new PolicyCatalog(new CapabilityRegistry()));

       self::assertContains('ticket.field.time_to_own.add', $input->blockedRules);
       self::assertContains('ticket.actor.requester.update', $input->blockedRules);
       self::assertArrayHasKey(BackendRulePolicy::OPTION_KEY, $input->options);
   }

   /** @dataProvider frontendOnlyControls */
   public function testMatrixPresencePersistsExplicitEmptyBackendSelection(array $blockedControls): void {
      $input = PolicySetInput::fromHttp([
          'profiles_id' => '4',
          'entities_id' => '0',
          'blocked_controls' => $blockedControls,
          'backend_controls_present' => '1',
      ], new PolicyCatalog(new CapabilityRegistry()));

      self::assertNotSame([], $input->blockedRules);
      self::assertSame('[]', $input->options[BackendRulePolicy::OPTION_KEY]);
   }

   public function testMatrixPresencePersistsExplicitEmptyBackendWithNoRestrictions(): void {
      $input = PolicySetInput::fromHttp([
          'profiles_id' => '4',
          'entities_id' => '0',
          'backend_controls_present' => '1',
      ], new PolicyCatalog(new CapabilityRegistry()));

      self::assertSame([], $input->blockedRules);
      self::assertSame('[]', $input->options[BackendRulePolicy::OPTION_KEY]);
   }

   /** @return iterable<string, array{array<string, list<string>>}> */
   public static function frontendOnlyControls(): iterable {
      yield 'opening only' => [['opening' => ['type']]];
      yield 'update only' => [['update' => ['status']]];
   }

   public function testBackendRequiresItsControlToBeSelected(): void {
       $this->expectException(\InvalidArgumentException::class);
       PolicySetInput::fromHttp([
           'profiles_id' => '4',
           'entities_id' => '0',
           'backend_controls' => ['tto'],
       ], new PolicyCatalog(new CapabilityRegistry()));
   }
}
