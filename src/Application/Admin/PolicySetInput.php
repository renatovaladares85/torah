<?php

namespace GlpiPlugin\Torah\Application\Admin;

use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use GlpiPlugin\Torah\Application\TicketControlCatalog;

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

       $controls = new TicketControlCatalog();
       $blockedControls = is_array($data['blocked_controls'] ?? null) ? $data['blocked_controls'] : [];
       $openingControls = is_array($blockedControls['opening'] ?? null) ? $blockedControls['opening'] : [];
       $updateControls = is_array($blockedControls['update'] ?? null) ? $blockedControls['update'] : [];
       $validatedRules = array_values(array_unique([
           ...$validatedRules,
           ...$controls->expand($openingControls, 'add'),
           ...$controls->expand($updateControls, 'update'),
       ]));

       $backendControls = is_array($data['backend_controls'] ?? null) ? $data['backend_controls'] : [];
       $backendRules = [];
      foreach ($backendControls as $controlKey) {
         if (!is_string($controlKey)) {
            throw new \InvalidArgumentException('The request contains an unknown backend ticket control.');
         }
         $isOpening = in_array($controlKey, $openingControls, true);
         $isUpdate = in_array($controlKey, $updateControls, true);
         if (!$isOpening && !$isUpdate) {
            throw new \InvalidArgumentException('Backend enforcement requires opening or update restriction.');
         }
         if ($isOpening) {
            $backendRules = [...$backendRules, ...$controls->expand([$controlKey], 'add')];
         }
         if ($isUpdate) {
            $backendRules = [...$backendRules, ...$controls->expand([$controlKey], 'update')];
         }
      }

       $options = [];

       // Structured controls are authoritative for the matrix. Legacy callers may
       // still submit blocked_rules and retain the legacy backend interpretation.
      if (array_key_exists('backend_controls', $data)) {
         $options[BackendRulePolicy::OPTION_KEY] = BackendRulePolicy::encode(
             BackendRulePolicy::normalize(array_values(array_unique($backendRules)), $catalog),
         );
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
