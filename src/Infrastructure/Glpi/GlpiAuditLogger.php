<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Application\AuditLogger;
use GlpiPlugin\Torah\Application\GlobalActorItemtypePolicy;
use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use Toolbox;

final class GlpiAuditLogger implements AuditLogger
{
   /** @var array<string, true> */
   private static array $technicalEvents = [];
   public function denied(
        AuthorizationContext $context,
        string $ruleKey,
        ?int $policySetId,
        string $source,
    ): void {
       $event = [
           'event'         => 'mutation_denied',
           'rule_key'      => $ruleKey,
           'policy_set_id' => $policySetId,
           'profile_id'    => $context->profileId,
           'entity_id'     => $context->entityId,
           'ticket_id'     => $context->ticketId,
           'effective_user_id' => $context->userId ?: null,
           'impersonator_id' => $context->impersonatorId,
           'execution_origin' => $context->executionOrigin,
           'source'        => $source,
       ];

       Toolbox::logInFile('torah', json_encode($event, JSON_THROW_ON_ERROR) . PHP_EOL);
   }

   public function contextUnresolved(int $entityId, int $ticketId, string $source): void {
      $deduplicationKey = "{$entityId}:{$ticketId}:{$source}";
      if (isset(self::$technicalEvents[$deduplicationKey])) {
         return;
      }
      self::$technicalEvents[$deduplicationKey] = true;
      Toolbox::logInFile('torah', json_encode([
          'event' => 'context_unresolved',
          'entity_id' => $entityId,
          'ticket_id' => $ticketId,
          'source' => $source,
      ], JSON_THROW_ON_ERROR) . PHP_EOL);
   }

   public function evaluationError(AuthorizationContext $context, string $source): void {
      Toolbox::logInFile('torah', json_encode([
          'event' => 'policy_evaluation_error',
          'profile_id' => $context->profileId,
          'entity_id' => $context->entityId,
          'ticket_id' => $context->ticketId,
          'effective_user_id' => $context->userId ?: null,
          'execution_origin' => $context->executionOrigin,
          'source' => $source,
      ], JSON_THROW_ON_ERROR) . PHP_EOL);
   }

   public function actorItemtypeDenied(
       AuthorizationContext $context,
       string $role,
       string $itemtype,
       string $source,
   ): void {
      Toolbox::logInFile('torah', json_encode([
          'event' => 'actor_itemtype_denied',
          'rule_key' => GlobalActorItemtypePolicy::key($role),
          'policy_set_id' => null,
          'actor_role' => $role,
          'actor_itemtype' => $itemtype,
          'profile_id' => $context->profileId,
          'entity_id' => $context->entityId,
          'ticket_id' => $context->ticketId,
          'effective_user_id' => $context->userId ?: null,
          'impersonator_id' => $context->impersonatorId,
          'execution_origin' => $context->executionOrigin,
          'source' => $source,
      ], JSON_THROW_ON_ERROR) . PHP_EOL);
   }
}
