<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Application\AuditLogger;
use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use Toolbox;

final class GlpiAuditLogger implements AuditLogger
{
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
           'user_id'       => $context->userId,
           'source'        => $source,
       ];

       Toolbox::logInFile('torah', json_encode($event, JSON_THROW_ON_ERROR) . PHP_EOL);
   }
}
