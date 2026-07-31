<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use GlpiPlugin\Torah\Application\BackendRulePolicy;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use GlpiPlugin\Torah\Application\TicketControlCatalog;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;
use Html;
use Plugin;
use Profile;

final class AdminPage
{
   public static function render(): void {
       $catalog = ServiceFactory::catalog();
       $policyModel = self::policyModel($catalog);
       Html::requireJs(Plugin::getWebDir('torah') . '/js/admin-policy-matrix.js');

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
           'new_actor_itemtypes' => self::actorItemtypes(null),
           'backend_profile' => (new BackendExecutionProfile())->get(),
           'backend_profile_dropdown' => Profile::dropdown([
               'name' => 'backend_profile_id',
               'value' => (new BackendExecutionProfile())->get() ?? 0,
               'display' => false,
           ]),
       ]);
   }

    /**
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
           'actor_itemtypes' => self::actorItemtypes($set),
           'backend_rule_keys' => self::backendRules($set, $catalog),
       ];
   }

    /** @return array<string, mixed> */
   private static function policyModel(PolicyCatalog $catalog): array {
      $fieldRules = self::fieldRules($catalog);
       $renderedRuleKeys = [];
      foreach ($fieldRules as $fieldRule) {
         foreach (['add_keys', 'update_keys'] as $ruleKeysName) {
            foreach ($fieldRule[$ruleKeysName] as $ruleKey) {
               $renderedRuleKeys[] = $ruleKey;
            }
         }
      }
       return [
           'field_rules'          => $fieldRules,
           'actor_itemtype_roles' => self::actorItemtypeRoles(),
           'rendered_rule_keys'   => array_values(array_unique($renderedRuleKeys)),
       ];
   }

    /** @return list<array<string, mixed>> */
   private static function fieldRules(PolicyCatalog $catalog): array {
       $rules = [];
      foreach ((new TicketControlCatalog())->all() as $definition) {
          $rules[] = [
              'key'        => $definition->key,
              'label'      => $definition['label'],
              'add_keys'   => $definition->addRuleKeys,
              'update_keys' => $definition->updateRuleKeys,
              'sensitive'  => $definition->sensitive,
          ];
      }

       return $rules;
   }

    /** @return list<array<string, mixed>> */
   private static function actorItemtypeRoles(): array {
       $itemtypeLabels = ActorItemtypePolicy::itemtypeLabels();
       $roles = [];
      foreach (ActorItemtypePolicy::roleLabels() as $role => $label) {
          $itemtypes = [];
         foreach ($itemtypeLabels as $itemtype => $itemtypeLabel) {
             $itemtypes[] = ['key' => $itemtype, 'label' => $itemtypeLabel];
         }
          $roles[] = ['key' => $role, 'label' => $label, 'itemtypes' => $itemtypes];
      }

       return $roles;
   }

    /** @return array<string, array<string, true>> */
   private static function actorItemtypes(?PolicySet $set): array {
       $actorItemtypes = [];
      foreach (array_keys(ActorItemtypePolicy::roleLabels()) as $role) {
          $allowed = $set === null ? ActorItemtypePolicy::ITEMTYPES : ActorItemtypePolicy::allowedFor($set, $role);
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
