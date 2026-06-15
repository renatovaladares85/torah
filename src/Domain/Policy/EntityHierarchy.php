<?php

namespace GlpiPlugin\Torah\Domain\Policy;

interface EntityHierarchy
{
    /** @return list<int> Entity IDs ordered from nearest to farthest. */
   public function ancestorsOf(int $entityId): array;
}
