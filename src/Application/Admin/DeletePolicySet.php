<?php

namespace GlpiPlugin\Torah\Application\Admin;

use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiPolicyStore;
use Session;

final class DeletePolicySet
{
   public function __construct(private readonly GlpiPolicyStore $store) {
   }

   public function execute(int $id): void {
       $policy = $id > 0 ? $this->store->find($id) : null;
      if ($policy === null) {
          throw new \InvalidArgumentException('Policy set does not exist.');
      }
      if (!Session::haveAccessToEntity($policy->entityId, $policy->recursive)) {
          throw new \InvalidArgumentException('The active session cannot access this policy scope.');
      }

       $this->store->delete($id);
   }
}
