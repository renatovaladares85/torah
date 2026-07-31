<?php

namespace GlpiPlugin\Torah\Application\Admin;

use GlpiPlugin\Torah\Application\GlobalActorItemtypePolicy;
use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiGlobalActorSettingsStore;

final class SaveGlobalActorSettings
{
   public function __construct(private readonly GlpiGlobalActorSettingsStore $store) {
   }

    /** @param array<string, mixed> $input */
   public function execute(array $input): void {
       $settings = [];
      foreach (array_keys(GlobalActorItemtypePolicy::roleLabels()) as $role) {
          $settings[$role] = GlobalActorItemtypePolicy::normalize($input[$role] ?? null);
         if ($settings[$role] === []) {
            throw new \InvalidArgumentException('At least one actor type must be selected.');
         }
      }

       $this->store->save($settings);
   }
}
