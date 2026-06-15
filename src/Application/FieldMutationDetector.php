<?php

namespace GlpiPlugin\Torah\Application;

final class FieldMutationDetector
{
    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $input
     */
   public function changes(array $existing, array $input, string $key): bool {
      if (!array_key_exists($key, $input)) {
          return false;
      }

      if ($key === '_contracts_id') {
          return (int) $input[$key] > 0;
      }

       $old = $existing[$key] ?? null;
       $new = $input[$key];

      if (($old === null || $old === '') && ($new === null || $new === '' || $new === 'NULL')) {
          return false;
      }

      if (is_scalar($old) && is_scalar($new) && (string) $old === (string) $new) {
          return false;
      }

       return $old != $new;
   }
}
