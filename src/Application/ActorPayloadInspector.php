<?php

namespace GlpiPlugin\Torah\Application;

final class ActorPayloadInspector
{
   private const FOREIGN_KEYS = [
        'User'     => 'users_id',
        'Group'    => 'groups_id',
        'Supplier' => 'suppliers_id',
    ];

    /**
     * @param array<string, mixed> $input
     * @param array<int|string, mixed> $existingActors
     */
   public function hasMutation(array $input, string $role, array $existingActors): bool {
      if (isset($input['_actors']) && is_array($input['_actors'])) {
          $submitted = is_array($input['_actors'][$role] ?? null) ? $input['_actors'][$role] : [];

          return $this->normalizeActors($submitted) !== $this->normalizeActors($existingActors);
      }

       $legacy = '_itil_' . $role;
      if (isset($input[$legacy]) && is_array($input[$legacy]) && $this->containsActorValue($input[$legacy])) {
          return true;
      }

       $existing = $this->existingIds($existingActors);
      foreach (self::FOREIGN_KEYS as $itemtype => $foreignKey) {
          $key = '_' . $foreignKey . '_' . $role;
         if (!empty($input[$key . '_deleted']) || !empty($input[$key . '_notif'])) {
             return true;
         }

         if (!array_key_exists($key, $input)) {
             continue;
         }

          $values = is_array($input[$key]) ? $input[$key] : [$input[$key]];
         foreach ($values as $value) {
             $actorId = (int) $value;
            if ($actorId > 0 && !isset($existing[$itemtype][$actorId])) {
                return true;
            }
         }
      }

      if (!isset($input['actortype'])) {
          return false;
      }

       $actorType = (string) $input['actortype'];
       $numericRoles = ['1' => 'requester', '2' => 'assign', '3' => 'observer'];

       return ($numericRoles[$actorType] ?? $actorType) === $role;
   }

    /**
     * @param array<string, mixed> $input
     * @param array<int|string, mixed> $existingActors
     * @return list<string>
     */
   public function addedItemtypes(array $input, string $role, array $existingActors): array {
       $itemtypes = [];
      if (isset($input['_actors']) && is_array($input['_actors'])) {
          $submitted = is_array($input['_actors'][$role] ?? null) ? $input['_actors'][$role] : [];
          $existing = array_fill_keys($this->normalizeActorIdentities($existingActors), true);
         foreach ($submitted as $actor) {
            if (!is_array($actor)) {
                continue;
            }

             $identity = $this->actorIdentity($actor);
            if ($identity === null || isset($existing[$identity])) {
                continue;
            }

             $itemtypes[] = (string) ($actor['itemtype'] ?? '');
         }
      }

       $legacy = '_itil_' . $role;
      if (isset($input[$legacy]) && is_array($input[$legacy]) && $this->containsActorValue($input[$legacy])) {
          $itemtypes[] = $this->legacyItilItemtype($input[$legacy]);
      }

       $existing = $this->existingIds($existingActors);
      foreach (self::FOREIGN_KEYS as $itemtype => $foreignKey) {
          $key = '_' . $foreignKey . '_' . $role;
         if (!array_key_exists($key, $input)) {
             continue;
         }

          $values = is_array($input[$key]) ? $input[$key] : [$input[$key]];
         foreach ($values as $value) {
             $actorId = (int) $value;
            if ($actorId > 0 && !isset($existing[$itemtype][$actorId])) {
                $itemtypes[] = $itemtype;
            }
         }
      }

       return array_values(array_unique(array_filter($itemtypes)));
   }

    /**
     * @param array<int|string, mixed> $actors
     * @return list<string>
     */
   private function normalizeActors(array $actors): array {
       $normalized = [];
      foreach ($actors as $actor) {
         if (!is_array($actor)) {
            continue;
         }

          $itemtype = (string) ($actor['itemtype'] ?? '');
          $itemsId = (int) ($actor['items_id'] ?? 0);
         if ($itemtype === '' || ($itemsId <= 0 && empty($actor['alternative_email']))) {
             continue;
         }

          $normalized[] = implode('|', [
              $itemtype,
              (string) $itemsId,
              (string) (int) ($actor['use_notification'] ?? 1),
              (string) ($actor['alternative_email'] ?? ''),
          ]);
      }

       sort($normalized);

       return array_values(array_unique($normalized));
   }

    /**
     * @param array<int|string, mixed> $actors
     * @return list<string>
     */
   private function normalizeActorIdentities(array $actors): array {
       $identities = [];
      foreach ($actors as $actor) {
         if (!is_array($actor)) {
             continue;
         }

          $identity = $this->actorIdentity($actor);
         if ($identity !== null) {
             $identities[] = $identity;
         }
      }

       return array_values(array_unique($identities));
   }

    /** @param array<string, mixed> $actor */
   private function actorIdentity(array $actor): ?string {
       $itemtype = (string) ($actor['itemtype'] ?? '');
       $itemsId = (int) ($actor['items_id'] ?? 0);
       $email = (string) ($actor['alternative_email'] ?? '');
      if ($itemtype === '' || ($itemsId <= 0 && $email === '')) {
          return null;
      }

       return implode('|', [$itemtype, (string) $itemsId, $email]);
   }

    /** @param array<string, mixed> $actor */
   private function containsActorValue(array $actor): bool {
      foreach (['users_id', 'groups_id', 'suppliers_id', 'alternative_email'] as $key) {
         if (!empty($actor[$key])) {
            return true;
         }
      }

       return false;
   }

    /** @param array<string, mixed> $actor */
   private function legacyItilItemtype(array $actor): string {
       $type = strtolower((string) ($actor['_type'] ?? ''));

       return match ($type) {
           'group'    => 'Group',
           'supplier' => 'Supplier',
           default    => 'User',
       };
   }

    /**
     * @param array<int|string, mixed> $actors
     * @return array<string, array<int, true>>
     */
   private function existingIds(array $actors): array {
       $existing = array_fill_keys(array_keys(self::FOREIGN_KEYS), []);
      foreach ($actors as $actor) {
         if (!is_array($actor)) {
            continue;
         }
          $itemtype = (string) ($actor['itemtype'] ?? '');
          $itemsId = (int) ($actor['items_id'] ?? 0);
         if (isset($existing[$itemtype]) && $itemsId > 0) {
             $existing[$itemtype][$itemsId] = true;
         }
      }

       return $existing;
   }
}
