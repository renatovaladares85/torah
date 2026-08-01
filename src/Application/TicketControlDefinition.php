<?php

namespace GlpiPlugin\Torah\Application;

final class TicketControlDefinition
{
   /**
    * @param list<string> $addRuleKeys
    * @param list<string> $updateRuleKeys
    * @param list<string> $selectors
    * @param list<array{strategy: string, selectors: list<string>}> $controls
    */
   public function __construct(
      public readonly string $key,
      public readonly string $label,
      public readonly array $addRuleKeys,
      public readonly array $updateRuleKeys,
      public readonly array $selectors = [],
      public readonly bool $sensitive = false,
      public readonly string $lockStrategy = 'text',
      public readonly array $controls = [],
   ) {
   }
}
