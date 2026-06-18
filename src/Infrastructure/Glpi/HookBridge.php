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

       ServiceFactory::guard()->guardRelationMutation($item, 'relation_update');
   }

   public static function preItemAdd(CommonDBTM $item): void {
      if ($item instanceof Ticket) {
          ServiceFactory::guard()->guardTicketAdd($item);

          return;
      }

       ServiceFactory::guard()->guardRelationMutation($item, 'relation_add');
   }

   public static function preItemDelete(CommonDBTM $item): void {
      if ($item instanceof SlaLevel_Ticket || $item instanceof OlaLevel_Ticket) {
          ServiceFactory::guard()->guardLevelAgreementDeletion($item);

          return;
      }

       ServiceFactory::guard()->guardRelationMutation($item, 'relation_delete');
   }

    /** @param array<string, mixed> $params */
   public static function postItemForm(array $params): void {
       TicketPolicyPresenter::render($params);
   }

    /** @param array<string, mixed> $params */
   public static function filterActors(array $params): array {
       return (new ActorListFilter())->filter($params);
   }
}
