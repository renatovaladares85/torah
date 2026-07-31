<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;
use Html;
use Plugin;
use Profile;

final class AdminPage
{
   public static function render(): void {
       $catalog = ServiceFactory::catalog();
       $policyModel = (new AdminPolicyModelBuilder())->build();
       echo Html::script('plugins/torah/js/admin-policy-matrix.js', ['version' => PLUGIN_TORAH_VERSION]);

       $profileGroups = [];
      foreach ((new GlpiPolicyStore())->all() as $set) {
         if (!\Session::haveAccessToEntity($set->entityId, $set->recursive)) {
            continue;
         }
          $viewModel = self::viewModel($set, $catalog, $policyModel);
          $profileId = $viewModel['profile_id'];
         if (!isset($profileGroups[$profileId])) {
             $profileGroups[$profileId] = [
                 'id'   => $profileId,
                 'name' => $viewModel['profile_name'],
                 'sets' => [],
             ];
         }
          $profileGroups[$profileId]['sets'][] = $viewModel;
      }

       TemplateRenderer::getInstance()->display('@torah/admin/policies.html.twig', [
           'action'              => Plugin::getWebDir('torah') . '/front/policy.form.php',
           'global_actor_action' => Plugin::getWebDir('torah') . '/front/global-actors.form.php',
           'global_actor_itemtypes' => self::globalActorItemtypes(),
           'policy_model'        => $policyModel,
           'profile_groups'      => array_values($profileGroups),
           'new_profile'         => Profile::dropdown([
               'name'    => 'profiles_id',
               'value'   => (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0),
               'display' => false,
           ]),
           'new_entity'          => Entity::dropdown([
               'name'    => 'entities_id',
               'value'   => (int) ($_SESSION['glpiactive_entity'] ?? 0),
               'display' => false,
           ]),
       ]);
   }

    /**
     * @param array<string, mixed> $policyModel
     * @return array<string, mixed>
     */
   private static function viewModel(PolicySet $set, PolicyCatalog $catalog, array $policyModel): array {
       $blockedRules = array_fill_keys($set->blockedRuleKeys(), true);
       $preservedBlockedRules = [];
      foreach ($set->blockedRuleKeys() as $ruleKey) {
         if ($catalog->has($ruleKey) && !in_array($ruleKey, $policyModel['rendered_rule_keys'], true)) {
             $preservedBlockedRules[] = $ruleKey;
         }
      }

       return [
           'id'              => $set->id,
           'profile_id'      => $set->profileId,
           'entity_id'       => $set->entityId,
           'profile_name'    => Dropdown::getDropdownName('glpi_profiles', $set->profileId),
           'entity_name'     => Dropdown::getDropdownName('glpi_entities', $set->entityId),
           'recursive'       => $set->recursive,
           'blocked_rules'   => $blockedRules,
           'preserved_blocked_rules' => $preservedBlockedRules,
           'backend_rule_keys' => self::backendRules($set, $catalog),
       ];
   }

    /** @return array<string, array<string, true>> */
   private static function globalActorItemtypes(): array {
       $actorItemtypes = [];
      foreach (ServiceFactory::globalActorSettings()->all() as $role => $allowed) {
          $actorItemtypes[$role] = array_fill_keys($allowed, true);
      }

       return $actorItemtypes;
   }

   /** @return array<string, true> */
   private static function backendRules(PolicySet $set, PolicyCatalog $catalog): array {
      $rules = [];
      foreach ($set->blockedRuleKeys() as $rule) {
         if (BackendRulePolicy::enforces($set, $rule, $catalog)) {
            $rules[$rule] = true;
         }
      }
      return $rules;
   }
}
