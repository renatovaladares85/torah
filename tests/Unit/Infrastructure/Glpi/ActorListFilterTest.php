<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use GlpiPlugin\Torah\Application\GlobalActorItemtypePolicy;
use GlpiPlugin\Torah\Infrastructure\Glpi\ActorListFilter;
use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiGlobalActorSettingsStore;
use GlpiPlugin\Torah\Infrastructure\Glpi\HookBridge;
use PHPUnit\Framework\TestCase;

final class ActorListFilterTest extends TestCase
{
   public function testFiltersCanonicalAndNamespacedActorItemtypes(): void {
      $filter = $this->filter(['requester' => ['User', 'Supplier']]);

      $canonical = $filter->filter($this->payload('requester', $this->actors()));
      self::assertSame(['User', 'Supplier'], array_column($canonical['actors'], 'itemtype'));

      $namespaced = $filter->filter($this->payload('requester', [
         ['itemtype' => '\\User'],
         ['itemtype' => '\\Group'],
         ['itemtype' => '\\Supplier'],
         ['itemtype' => '\\Namespace\\User'],
         ['itemtype' => 'Namespace\\Group'],
         ['itemtype' => '\\Namespace\\Supplier'],
      ]));
      self::assertSame([
         '\\User',
         '\\Supplier',
         '\\Namespace\\User',
         '\\Namespace\\Supplier',
      ], array_column($namespaced['actors'], 'itemtype'));
   }

   public function testPreservesNonEmptyStructuralGroupsAndRemovesEmptyOnes(): void {
      $actors = [[
         'itemtype' => 'Entity',
         'text' => 'Root entity',
         'children' => [
            ['itemtype' => 'Group', 'id' => 'Group_1'],
            ['itemtype' => 'Supplier', 'id' => 'Supplier_1'],
         ],
      ]];

      $withSupplier = $this->filter(['requester' => ['Supplier']])->filter($this->payload('requester', $actors));
      self::assertSame('Entity', $withSupplier['actors'][0]['itemtype']);
      self::assertSame(['Supplier'], array_column($withSupplier['actors'][0]['children'], 'itemtype'));

      $empty = $this->filter(['requester' => ['User']])->filter($this->payload('requester', $actors));
      self::assertSame([], $empty['actors']);
   }

   public function testEachRoleUsesOnlyItsOwnConfiguration(): void {
      $filter = $this->filter([
         'requester' => ['User', 'Supplier'],
         'observer' => ['User', 'Group', 'Supplier'],
         'assign' => ['User', 'Supplier'],
      ]);

      self::assertSame(['User', 'Supplier'], array_column($filter->filter($this->payload('requester', $this->actors()))['actors'], 'itemtype'));
      self::assertSame(['User', 'Group', 'Supplier'], array_column($filter->filter($this->payload('observer', $this->actors()))['actors'], 'itemtype'));
      self::assertSame(['User', 'Supplier'], array_column($filter->filter($this->payload('assign', $this->actors()))['actors'], 'itemtype'));
      self::assertSame(['User', 'Supplier'], array_column($filter->filter($this->payload('assignee', $this->actors()))['actors'], 'itemtype'));
   }

   public function testAllSupportedCombinationsReturnExactlyTheConfiguredTypes(): void {
      $combinations = [
         ['User'],
         ['Group'],
         ['Supplier'],
         ['User', 'Group'],
         ['User', 'Supplier'],
         ['Group', 'Supplier'],
         ['User', 'Group', 'Supplier'],
      ];

      foreach (['requester', 'observer', 'assign'] as $role) {
         foreach ($combinations as $allowed) {
            $result = $this->filter([$role => $allowed])->filter($this->payload($role, $this->actors()));
            self::assertSame($allowed, array_column($result['actors'], 'itemtype'), $role . ': ' . implode(', ', $allowed));
         }
      }
   }

   public function testPreservesUnknownNodesAndIgnoresInvalidEntries(): void {
      $result = $this->filter(['requester' => ['User']])->filter($this->payload('requester', [
         ['itemtype' => 'CustomActor', 'id' => 'custom'],
         ['text' => 'Structural item'],
         ['itemtype' => ''],
         ['itemtype' => '\\'],
         ['itemtype' => 42],
         'invalid',
      ]));

      self::assertSame([
         ['itemtype' => 'CustomActor', 'id' => 'custom'],
         ['text' => 'Structural item'],
         ['itemtype' => ''],
         ['itemtype' => '\\'],
         ['itemtype' => 42],
      ], $result['actors']);

      $invalidActors = $this->filter(['requester' => ['User']])->filter([
         'actors' => 'invalid',
         'params' => ['actortype' => 'requester'],
      ]);
      self::assertSame([], $invalidActors['actors']);
   }

   public function testHookBridgePreservesTheCompletePayload(): void {
      $this->configure(['requester' => ['User']]);

      $payload = $this->payload('requester', $this->actors()) + ['unchanged' => ['value' => true]];
      $result = HookBridge::filterActors($payload);

      self::assertSame(['User'], array_column($result['actors'], 'itemtype'));
      self::assertSame($payload['params'], $result['params']);
      self::assertSame($payload['unchanged'], $result['unchanged']);
   }

   /** @param array<string, list<string>> $overrides */
   private function filter(array $overrides): ActorListFilter {
      $this->configure($overrides);

      return new ActorListFilter(new GlpiGlobalActorSettingsStore());
   }

   /** @param array<string, list<string>> $overrides */
   private function configure(array $overrides): void {
      $values = array_replace(GlobalActorItemtypePolicy::defaults(), $overrides);
      $encoded = [];
      foreach ($values as $role => $itemtypes) {
         $encoded[GlobalActorItemtypePolicy::key($role)] = GlobalActorItemtypePolicy::encode($itemtypes);
      }

      if (!method_exists(\Config::class, 'setTestConfigurationValues')) {
         self::markTestSkipped('The isolated Config test stub is unavailable.');
      }
      \Config::setTestConfigurationValues(GlobalActorItemtypePolicy::CONTEXT, $encoded);
   }

   /** @return list<array<string, string>> */
   private function actors(): array {
      return [
         ['itemtype' => 'User'],
         ['itemtype' => 'Group'],
         ['itemtype' => 'Supplier'],
      ];
   }

   /**
    * @param array<int, mixed> $actors
    * @return array<string, mixed>
    */
   private function payload(string $role, array $actors): array {
      return [
         'actors' => $actors,
         'params' => [
            'actortype' => $role,
            'returned_itemtypes' => [],
         ],
      ];
   }
}
