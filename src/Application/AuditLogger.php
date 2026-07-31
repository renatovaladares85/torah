<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;

interface AuditLogger
{
   public function denied(
        AuthorizationContext $context,
        string $ruleKey,
        ?int $policySetId,
        string $source,
    ): void;

   public function contextUnresolved(int $entityId, int $ticketId, string $source): void;

   public function evaluationError(AuthorizationContext $context, string $source): void;

   public function actorItemtypeDenied(
        AuthorizationContext $context,
        string $role,
        string $itemtype,
        string $source,
    ): void;
}
