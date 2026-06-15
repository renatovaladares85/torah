<?php

namespace GlpiPlugin\Torah\Domain\Policy;

interface PolicyRepository
{
   public function findExact(int $profileId, int $entityId): ?PolicySet;

    /**
     * Ancestors must be ordered from nearest to farthest.
     *
     * @param list<int> $ancestorEntityIds
     */
   public function findNearestRecursive(int $profileId, array $ancestorEntityIds): ?PolicySet;
}
