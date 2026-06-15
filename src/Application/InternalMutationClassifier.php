<?php

namespace GlpiPlugin\Torah\Application;

use CommonITILActor;

final class InternalMutationClassifier
{
    /** @param array<string, mixed> $input */
   public function isDerivedStatusUpdate(array $input): bool {
       $meaningfulKeys = array_values(array_filter(
           array_keys($input),
           static fn (string $key): bool => !in_array(
                $key,
                ['id', '_no_history', '_no_message', '_from_assignment'],
                true,
            ),
       ));

      if ($meaningfulKeys !== ['status']) {
          return false;
      }

      foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20) as $frame) {
          $class = (string) ($frame['class'] ?? '');
         if (is_a($class, CommonITILActor::class, true)) {
             return true;
         }
      }

       return false;
   }
}
