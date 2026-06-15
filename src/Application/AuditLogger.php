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
}
