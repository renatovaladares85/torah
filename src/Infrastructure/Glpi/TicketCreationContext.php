<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

/** Request-local marker for relations emitted by Ticket::post_addItem(). */
final class TicketCreationContext
{
   /** @var array<int, true> */
   private static array $ticketIds = [];
   private static bool $pending = false;

   public static function beginPending(): void {
      self::$pending = true;
   }

   public static function begin(int $ticketId): void {
      if ($ticketId > 0) {
         self::$ticketIds[$ticketId] = true;
      }
   }

   public static function contains(int $ticketId): bool {
      return self::$pending || isset(self::$ticketIds[$ticketId]);
   }

   public static function end(int $ticketId): void {
      unset(self::$ticketIds[$ticketId]);
      self::$pending = false;
   }
}
