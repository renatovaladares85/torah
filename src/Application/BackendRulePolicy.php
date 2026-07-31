<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Policy\PolicySet;

/** Stores the explicit backend-enforcement choices for a policy set. */
final class BackendRulePolicy
{
   public const OPTION_KEY = 'ticket.backend_rule_keys';

   /** @param list<mixed> $keys
    *  @return list<string> */
   public static function normalize(array $keys, PolicyCatalog $catalog): array {
      $normalized = [];
      foreach ($keys as $key) {
         if (!is_string($key) || !$catalog->has($key)) {
            throw new \InvalidArgumentException('The request contains an unknown backend policy rule.');
         }
         $normalized[$key] = true;
      }

      return array_keys($normalized);
   }

   /** @param list<string> $keys */
   public static function encode(array $keys): string {
      return json_encode(array_values(array_unique($keys)), JSON_THROW_ON_ERROR);
   }

   /** @return list<string> */
   public static function decode(?string $value, PolicyCatalog $catalog): array {
      if ($value === null || $value === '') {
         return [];
      }

      try {
         $decoded = json_decode($value, true, 32, JSON_THROW_ON_ERROR);
      } catch (\JsonException) {
         return [];
      }

      if (!is_array($decoded)) {
         return [];
      }

      try {
         return self::normalize($decoded, $catalog);
      } catch (\InvalidArgumentException) {
         return [];
      }
   }

   public static function isExplicit(PolicySet $set): bool {
      return $set->option(self::OPTION_KEY) !== null;
   }

   public static function enforces(PolicySet $set, string $ruleKey, PolicyCatalog $catalog): bool {
      // Policies saved before 0.3.0 did enforce every blocked rule in hooks.
      if (!self::isExplicit($set)) {
         return $set->blocks($ruleKey);
      }

      return in_array($ruleKey, self::decode($set->option(self::OPTION_KEY), $catalog), true);
   }
}
