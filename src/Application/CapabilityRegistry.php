<?php

namespace GlpiPlugin\Torah\Application;

final class CapabilityRegistry
{
   private static ?self $instance = null;

    /** @var array<string, CapabilityDefinition> */
   private array $definitions = [];

   public static function instance(): self {
       return self::$instance ??= new self();
   }

   public function register(string $key, string $label, string $provider): void {
      if (preg_match('/^[a-z][a-z0-9_.-]{2,190}$/', $key) !== 1) {
          throw new \InvalidArgumentException('Capability keys must be stable lowercase identifiers.');
      }

      if (isset($this->definitions[$key])) {
          throw new \InvalidArgumentException(sprintf('Capability "%s" is already registered.', $key));
      }

       $this->definitions[$key] = new CapabilityDefinition($key, $label, $provider);
   }

    /** @return array<string, CapabilityDefinition> */
   public function all(): array {
       ksort($this->definitions);

       return $this->definitions;
   }

   public function has(string $key): bool {
       return isset($this->definitions[$key]);
   }
}
