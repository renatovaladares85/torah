<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Config;
use GlpiPlugin\Torah\Application\GlobalActorItemtypePolicy;

final class GlpiGlobalActorSettingsStore
{
    /** @return array<string, list<string>> */
   public function all(): array {
       $values = $this->raw();
       $settings = [];
      foreach (GlobalActorItemtypePolicy::keys() as $role => $key) {
          $settings[$role] = GlobalActorItemtypePolicy::decode($values[$key] ?? null);
      }

       return $settings;
   }

    /** @return array<string, mixed> */
   public function raw(): array {
       $values = Config::getConfigurationValues(GlobalActorItemtypePolicy::CONTEXT);

       return is_array($values) ? $values : [];
   }

   public function hasActorConfiguration(): bool {
       $values = $this->raw();
      foreach (GlobalActorItemtypePolicy::keys() as $key) {
         if (array_key_exists($key, $values)) {
            return true;
         }
      }

       return false;
   }

    /** @param array<string, mixed> $settings */
   public function save(array $settings): void {
       $values = [];
      foreach (GlobalActorItemtypePolicy::keys() as $role => $key) {
          $values[$key] = GlobalActorItemtypePolicy::encode($settings[$role] ?? null);
      }
       Config::setConfigurationValues(GlobalActorItemtypePolicy::CONTEXT, $values);
   }

    /** @param array<string, mixed> $values */
   public function saveRaw(array $values): void {
       Config::setConfigurationValues(GlobalActorItemtypePolicy::CONTEXT, $values);
   }

   public function clear(): void {
      $keys = array_keys($this->raw());
      if ($keys !== []) {
         Config::deleteConfigurationValues(GlobalActorItemtypePolicy::CONTEXT, $keys);
      }
   }
}
