<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Dropdown;
use Session;
use Supplier;
use Toolbox;

final class ActorListFilter
{
   /** @param array<string, mixed> $payload */
   public function filter(array $payload): array {
      $actors = is_array($payload['actors'] ?? null) ? $payload['actors'] : [];
      $params = is_array($payload['params'] ?? null) ? $payload['params'] : [];
      $actorType = (string) ($params['actortype'] ?? '');
      $returnedItemtypes = is_array($params['returned_itemtypes'] ?? null) ? $params['returned_itemtypes'] : [];

      if (!in_array($actorType, ['requester', 'observer'], true) || !in_array(Supplier::class, $returnedItemtypes, true)) {
         $payload['actors'] = $actors;

         return $payload;
      }

      if ($this->supplierFieldIsHidden($params, $actorType)) {
         $payload['actors'] = $actors;

         return $payload;
      }

      $payload['actors'] = $this->mergeSuppliers($actors, $this->supplierActors($params));

      return $payload;
   }

   /** @param array<string, mixed> $params */
   private function supplierFieldIsHidden(array $params, string $actorType): bool {
      $templateClass = (string) ($params['itiltemplate_class'] ?? '');
      if ($templateClass === '' || !is_subclass_of($templateClass, 'ITILTemplate')) {
         return false;
      }

      $template = new $templateClass();
      $template->getFromDBWithData((int) ($params['itiltemplates_id'] ?? 0));

      return $template->isHiddenField("_suppliers_id_{$actorType}");
   }

   /**
    * @param array<string, mixed> $params
    * @return list<array<string, mixed>>
    */
   private function supplierActors(array $params): array {
      $entityRestrict = -1;
      if (isset($params['entity_restrict'])) {
         $entityRestrict = Session::getMatchingActiveEntities(Toolbox::jsonDecode($params['entity_restrict']));
      }

      $condition = [];
      if (empty($params['inactive_deleted'])) {
         $condition = Dropdown::addNewCondition(['is_active' => 1]);
      }

      $supplierParams = [
          'itemtype'            => Supplier::class,
          'display_emptychoice' => false,
          'searchText'          => $params['searchText'] ?? null,
          'entity_restrict'     => $entityRestrict,
          'condition'           => $condition,
      ];
      $supplierIdor = Session::getNewIDORToken(Supplier::class, [
          'entity_restrict' => $entityRestrict,
          'condition'       => $condition,
      ]);

      $dropdown = Dropdown::getDropdownValue($supplierParams + ['_idor_token' => $supplierIdor], false);
      $results = is_array($dropdown['results'] ?? null) ? $dropdown['results'] : [];

      foreach ($results as &$group) {
         if (!is_array($group['children'] ?? null)) {
            continue;
         }

         foreach ($group['children'] as &$child) {
            $supplier = new Supplier();
            $supplier->getFromDB((int) ($child['id'] ?? 0));

            $child['items_id'] = (int) ($child['id'] ?? 0);
            $child['id'] = 'Supplier_' . $child['items_id'];
            $child['itemtype'] = Supplier::class;
            $child['use_notification'] = strlen((string) ($supplier->fields['email'] ?? '')) > 0 ? 1 : 0;
            $child['default_email'] = (string) ($supplier->fields['email'] ?? '');
            $child['alternative_email'] = '';
         }
      }

      return array_values($results);
   }

   /**
    * @param list<array<string, mixed>> $actors
    * @param list<array<string, mixed>> $supplierGroups
    * @return list<array<string, mixed>>
    */
   private function mergeSuppliers(array $actors, array $supplierGroups): array {
      $entityIndexes = [];
      foreach ($actors as $index => $actorGroup) {
         if (($actorGroup['itemtype'] ?? null) === 'Entity' && isset($actorGroup['text'])) {
            $entityIndexes[(string) $actorGroup['text']] = $index;
         }
      }

      foreach ($supplierGroups as $supplierGroup) {
         $entity = (string) ($supplierGroup['text'] ?? '');
         if ($entity !== '' && isset($entityIndexes[$entity])) {
            $index = $entityIndexes[$entity];
            $existingChildren = is_array($actors[$index]['children'] ?? null) ? $actors[$index]['children'] : [];
            $newChildren = is_array($supplierGroup['children'] ?? null) ? $supplierGroup['children'] : [];
            $actors[$index]['children'] = array_merge($existingChildren, $newChildren);

            continue;
         }

         $actors[] = $supplierGroup;
      }

      return array_values($actors);
   }
}
