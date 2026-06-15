<?php

namespace GlpiPlugin\Torah\Application;

final class CapabilityDefinition
{
   public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $provider,
    ) {
   }
}
