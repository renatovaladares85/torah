<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Domain\Policy\EntityHierarchy;

final class GlpiEntityHierarchy implements EntityHierarchy
{
   public function ancestorsOf(int $entityId): array {
      if ($entityId <= 0) {
          return [];
      }

       $ancestors = getAncestorsOf('glpi_entities', $entityId);
      if (!is_array($ancestors)) {
          return [];
      }

       return array_values(array_reverse(array_map('intval', $ancestors)));
   }
}
