<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Policy\PolicySet;

final class ActorItemtypePolicy
{
   public const ITEMTYPES = ['User', 'Group', 'Supplier'];

   /** @return array<string, string> */
   public static function roleLabels(): array {
       return [
           'requester' => __('Requester', 'torah'),
           'observer'  => __('Observer', 'torah'),
           'assign'    => __('Assignee', 'torah'),
       ];
   }

   /** @return array<string, string> */
   public static function itemtypeLabels(): array {
       return [
           'User'     => __('Users', 'torah'),
           'Group'    => __('Groups', 'torah'),
           'Supplier' => __('Suppliers', 'torah'),
       ];
   }

   public static function optionKey(string $role): string {
       return sprintf('ticket.actor.%s.allowed_itemtypes', $role === 'assign' ? 'assignee' : $role);
   }

   public static function isOptionKey(string $key): bool {
      foreach (array_keys(self::roleLabels()) as $role) {
         if ($key === self::optionKey($role)) {
             return true;
         }
      }

       return false;
   }

   /**
    * @param mixed $value
    * @return list<string>
    */
   public static function normalize(mixed $value): array {
      if (!is_array($value)) {
          return self::ITEMTYPES;
      }

       $allowed = array_fill_keys(self::ITEMTYPES, true);
       $normalized = [];
      foreach ($value as $itemtype) {
         if (!is_string($itemtype) || !isset($allowed[$itemtype])) {
             continue;
         }
          $normalized[] = $itemtype;
      }

       return array_values(array_unique($normalized));
   }

   /** @return list<string> */
   public static function allowedFor(PolicySet $policy, string $role): array {
       $encoded = $policy->option(self::optionKey($role));
      if ($encoded === null) {
          return self::ITEMTYPES;
      }

      try {
          $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
      } catch (\Throwable) {
          return self::ITEMTYPES;
      }

       $normalized = self::normalize($decoded);

       return $normalized === [] ? self::ITEMTYPES : $normalized;
   }

   /** @param list<string> $itemtypes */
   public static function encode(array $itemtypes): string {
       $normalized = self::normalize($itemtypes);
      if ($normalized === []) {
          throw new \InvalidArgumentException('At least one actor type must be selected.');
      }

       return json_encode($normalized, JSON_THROW_ON_ERROR);
   }
}
