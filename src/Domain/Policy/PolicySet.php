<?php

namespace GlpiPlugin\Torah\Domain\Policy;

final class PolicySet
{
    /** @var array<string, true> */
   private array $blockedRules;

    /**
     * @param list<string> $blockedRules
     */
   public function __construct(
        public readonly ?int $id,
        public readonly int $profileId,
        public readonly int $entityId,
        public readonly bool $recursive,
        array $blockedRules,
    ) {
       $this->blockedRules = array_fill_keys(array_values(array_unique($blockedRules)), true);
   }

   public function blocks(string $ruleKey): bool {
       return isset($this->blockedRules[$ruleKey]);
   }

    /** @return list<string> */
   public function blockedRuleKeys(): array {
       return array_keys($this->blockedRules);
   }
}
