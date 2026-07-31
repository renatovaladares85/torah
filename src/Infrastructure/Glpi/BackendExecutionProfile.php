<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Config;
use Profile;

final class BackendExecutionProfile
{
   public const CONFIG_CONTEXT = 'plugin:torah';
   public const CONFIG_KEY = 'backend_profile_id';

   public function get(): ?int {
      try {
         $values = Config::getConfigurationValues(self::CONFIG_CONTEXT);
      } catch (\Throwable) {
         return null;
      }
      $id = (int) ($values[self::CONFIG_KEY] ?? 0);
      return $id > 0 ? $id : null;
   }

   public function save(?int $profileId): void {
      if ($profileId !== null) {
         $profile = new Profile();
         if (!$profile->getFromDB($profileId)) {
            throw new \InvalidArgumentException('The backend profile does not exist.');
         }
      }
      Config::setConfigurationValues(self::CONFIG_CONTEXT, [self::CONFIG_KEY => $profileId ?? 0]);
   }
}
