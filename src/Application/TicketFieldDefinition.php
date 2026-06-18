<?php

namespace GlpiPlugin\Torah\Application;

final class TicketFieldDefinition
{
    /** @param list<string> $selectors */
   public function __construct(
       public readonly string $key,
       public readonly string $label,
       public readonly string $inputKey,
       public readonly array $selectors,
   ) {
   }
}
