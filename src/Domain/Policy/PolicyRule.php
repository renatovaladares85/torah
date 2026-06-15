<?php

namespace GlpiPlugin\Torah\Domain\Policy;

final class PolicyRule
{
    /**
     * @param list<string> $inputKeys
     * @param list<string> $selectors
     */
   public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly string $label,
        public readonly array $inputKeys = [],
        public readonly array $selectors = [],
    ) {
   }
}
