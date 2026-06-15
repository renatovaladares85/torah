<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use CommonDBTM;
use OlaLevel_Ticket;
use SlaLevel_Ticket;
use Ticket;

final class HookBridge
{
   public static function preItemUpdate(CommonDBTM $item): void {
      if ($item instanceof Ticket) {
          ServiceFactory::guard()->guardTicketUpdate($item);

          return;
      }

       ServiceFactory::guard()->guardRelationMutation($item);
   }

   public static function preItemAdd(CommonDBTM $item): void {
       ServiceFactory::guard()->guardRelationMutation($item);
   }

   public static function preItemDelete(CommonDBTM $item): void {
      if ($item instanceof SlaLevel_Ticket || $item instanceof OlaLevel_Ticket) {
          ServiceFactory::guard()->guardLevelAgreementDeletion($item);

          return;
      }

       ServiceFactory::guard()->guardRelationMutation($item);
   }

    /** @param array<string, mixed> $params */
   public static function postItemForm(array $params): void {
       TicketPolicyPresenter::render($params);
   }
}
