<?php

namespace GlpiPlugin\Torah\Domain\Authorization;

final class AuthorizationDecision
{
   private function __construct(
        public readonly bool $allowed,
        public readonly ?string $ruleKey,
        public readonly ?int $policySetId,
    ) {
   }

   public static function allow(): self {
       return new self(true, null, null);
   }

   public static function deny(string $ruleKey, ?int $policySetId): self {
       return new self(false, $ruleKey, $policySetId);
   }
}
