<?php

namespace GlpiPlugin\Torah\Application;

/** Defines and validates Torah's global ticket actor configuration. */
final class GlobalActorItemtypePolicy
{
   public const CONTEXT = 'plugin:torah';
   public const ITEMTYPES = ['User', 'Group', 'Supplier'];
   public const BACKUP_KEY = 'legacy_actor_itemtypes_backup_v1';
   public const MIGRATION_KEY = 'actor_itemtypes_global_migration_v1_completed';

    /** @return array<string, string> */
   public static function roleLabels(): array {
       return [
           'requester' => __('Requester', 'torah'),
           'observer' => __('Observer', 'torah'),
           'assign' => __('Assigned to', 'torah'),
       ];
   }

    /** @return array<string, string> */
   public static function itemtypeLabels(): array {
       return [
           'User' => __('Users', 'torah'),
           'Group' => __('Groups', 'torah'),
           'Supplier' => __('Suppliers', 'torah'),
       ];
   }

    /** @return array<string, string> */
   public static function keys(): array {
       return [
           'requester' => 'actor_itemtypes.requester',
           'observer' => 'actor_itemtypes.observer',
           'assign' => 'actor_itemtypes.assignee',
       ];
   }

   public static function key(string $role): string {
       $keys = self::keys();
      if (!isset($keys[$role])) {
          throw new \InvalidArgumentException('The actor role is unknown.');
      }

       return $keys[$role];
   }

    /** @return array<string, list<string>> */
   public static function defaults(): array {
       return array_fill_keys(array_keys(self::roleLabels()), self::ITEMTYPES);
   }

    /** @param mixed $itemtypes
     *  @return list<string>
     */
   public static function normalize(mixed $itemtypes): array {
      if (!is_array($itemtypes)) {
          return [];
      }

       $known = array_fill_keys(self::ITEMTYPES, true);
      $selected = [];
      foreach ($itemtypes as $itemtype) {
         if (is_string($itemtype) && isset($known[$itemtype])) {
            $selected[$itemtype] = true;
         }
      }

      return array_values(array_filter(self::ITEMTYPES, static fn (string $itemtype): bool => isset($selected[$itemtype])));
   }

    /** @param mixed $value
     *  @return list<string>
     */
   public static function decode(mixed $value): array {
      if (!is_string($value)) {
          return self::ITEMTYPES;
      }

      try {
          $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
      } catch (\Throwable) {
          return self::ITEMTYPES;
      }

       $normalized = self::normalize($decoded);

       return $normalized === [] ? self::ITEMTYPES : $normalized;
   }

    /** @param mixed $itemtypes */
   public static function encode(mixed $itemtypes): string {
       $normalized = self::normalize($itemtypes);
      if ($normalized === []) {
          throw new \InvalidArgumentException('At least one actor type must be selected.');
      }

       return json_encode($normalized, JSON_THROW_ON_ERROR);
   }

   public static function legacyOptionKey(string $role): string {
      if (!isset(self::roleLabels()[$role])) {
          throw new \InvalidArgumentException('The actor role is unknown.');
      }

       return sprintf('ticket.actor.%s.allowed_itemtypes', $role === 'assign' ? 'assignee' : $role);
   }

   public static function isLegacyOptionKey(string $key): bool {
      foreach (array_keys(self::roleLabels()) as $role) {
         if ($key === self::legacyOptionKey($role)) {
            return true;
         }
      }

       return false;
   }
}
