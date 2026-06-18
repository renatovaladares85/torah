<?php

namespace GlpiPlugin\Torah\Application\Admin;

use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use GlpiPlugin\Torah\Application\PolicyCatalog;

final class PolicySetInput
{
    /**
     * @param list<string> $blockedRules
     * @param array<string, string> $options
     */
   private function __construct(
        public readonly ?int $id,
        public readonly int $profileId,
        public readonly int $entityId,
        public readonly bool $recursive,
        public readonly array $blockedRules,
        public readonly array $options,
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

       $options = [];
       $actorItemtypes = is_array($data['actor_itemtypes'] ?? null) ? $data['actor_itemtypes'] : [];
      $presentActorItemtypes = is_array($data['actor_itemtypes_present'] ?? null) ? $data['actor_itemtypes_present'] : [];
      foreach (array_keys(ActorItemtypePolicy::roleLabels()) as $role) {
         if (array_key_exists($role, $presentActorItemtypes)) {
             $submitted = is_array($actorItemtypes[$role] ?? null) ? $actorItemtypes[$role] : [];
         } else {
             $submitted = is_array($actorItemtypes[$role] ?? null) ? $actorItemtypes[$role] : ActorItemtypePolicy::ITEMTYPES;
         }
          $selected = ActorItemtypePolicy::normalize($submitted);
         if ($selected === []) {
             throw new \InvalidArgumentException('At least one actor type must be selected.');
         }
          $options[ActorItemtypePolicy::optionKey($role)] = ActorItemtypePolicy::encode($selected);
      }

       return new self(
           $id,
           $profileId,
           $entityId,
           isset($data['is_recursive']) && (int) $data['is_recursive'] === 1,
           array_values(array_unique($validatedRules)),
           $options,
       );
   }
}
