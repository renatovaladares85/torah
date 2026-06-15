<?php

namespace GlpiPlugin\Torah\Application\Admin;

use GlpiPlugin\Torah\Application\PolicyCatalog;

final class PolicySetInput
{
    /** @param list<string> $blockedRules */
   private function __construct(
        public readonly ?int $id,
        public readonly int $profileId,
        public readonly int $entityId,
        public readonly bool $recursive,
        public readonly array $blockedRules,
    ) {
   }

    /** @param array<string, mixed> $data */
   public static function fromHttp(array $data, PolicyCatalog $catalog): self {
       $id = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
       $profileId = (int) ($data['profiles_id'] ?? 0);
       $entityId = (int) ($data['entities_id'] ?? -1);
       $rules = is_array($data['blocked_rules'] ?? null) ? $data['blocked_rules'] : [];

      if ($profileId <= 0 || $entityId < 0) {
          throw new \InvalidArgumentException('Profile and entity are required.');
      }

       $validatedRules = [];
      foreach ($rules as $ruleKey) {
         if (!is_string($ruleKey) || !$catalog->has($ruleKey)) {
             throw new \InvalidArgumentException('The request contains an unknown policy rule.');
         }
          $validatedRules[] = $ruleKey;
      }

       return new self(
           $id,
           $profileId,
           $entityId,
           isset($data['is_recursive']) && (int) $data['is_recursive'] === 1,
           array_values(array_unique($validatedRules)),
       );
   }
}
