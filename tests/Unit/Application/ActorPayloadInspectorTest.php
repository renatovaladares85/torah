<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\ActorPayloadInspector;
use PHPUnit\Framework\TestCase;

final class ActorPayloadInspectorTest extends TestCase
{
   private ActorPayloadInspector $inspector;

   protected function setUp(): void {
       $this->inspector = new ActorPayloadInspector();
   }

   public function testUnchangedActorsPayloadIsNotAMutation(): void {
       $actors = [$this->actor('User', 10)];
       $input = ['_actors' => ['requester' => $actors]];

       self::assertFalse($this->inspector->hasMutation($input, 'requester', $actors));
   }

   public function testActorsPayloadDetectsUserGroupSupplierAndNotificationChanges(): void {
       $existing = [$this->actor('User', 10)];

       self::assertTrue($this->inspector->hasMutation(
           ['_actors' => ['assign' => [$this->actor('Group', 20)]]],
           'assign',
           $existing,
       ));
       self::assertTrue($this->inspector->hasMutation(
           ['_actors' => ['assign' => [$this->actor('Supplier', 30)]]],
           'assign',
           $existing,
       ));
       self::assertTrue($this->inspector->hasMutation(
           ['_actors' => ['requester' => [$this->actor('User', 10, 0)]]],
           'requester',
           $existing,
       ));
   }

   public function testLegacyAddDeleteAndItilPayloadsAreDetected(): void {
       $existing = [$this->actor('User', 10)];

       self::assertFalse($this->inspector->hasMutation(
           ['_users_id_requester' => [10]],
           'requester',
           $existing,
       ));
       self::assertTrue($this->inspector->hasMutation(
           ['_groups_id_requester' => [20]],
           'requester',
           $existing,
       ));
       self::assertTrue($this->inspector->hasMutation(
           ['_users_id_requester_deleted' => [['items_id' => 10]]],
           'requester',
           $existing,
       ));
       self::assertTrue($this->inspector->hasMutation(
           ['_itil_requester' => ['users_id' => 11]],
           'requester',
           $existing,
       ));
   }

   public function testStringAndNumericActorTypePayloadsAreDetected(): void {
       self::assertTrue($this->inspector->hasMutation(['actortype' => 'observer'], 'observer', []));
       self::assertTrue($this->inspector->hasMutation(['actortype' => 2], 'assign', []));
       self::assertFalse($this->inspector->hasMutation(['actortype' => 1], 'observer', []));
   }

    /** @return array<string, mixed> */
   private function actor(string $itemtype, int $itemsId, int $useNotification = 1): array {
       return [
           'itemtype'          => $itemtype,
           'items_id'          => $itemsId,
           'use_notification'  => $useNotification,
           'alternative_email' => '',
       ];
   }
}
