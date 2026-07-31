<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use GlpiPlugin\Torah\Application\PolicyCatalog;
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
       ];
   }

    /** @return array<string, mixed> */
   private static function policyModel(PolicyCatalog $catalog): array {
       $actorRules = [];
      foreach ($catalog->all() as $rule) {
         if ($rule->domain === 'assistance' && $rule->object === 'ticket' && $rule->group === 'actors') {
             $actorRules[] = $rule;
         }
      }

       $fieldRules = self::fieldRules($catalog);
       $renderedRuleKeys = [];
      foreach ($fieldRules as $fieldRule) {
         foreach (['add_key', 'update_key'] as $ruleKeyName) {
            if ($fieldRule[$ruleKeyName] !== null) {
                $renderedRuleKeys[] = $fieldRule[$ruleKeyName];
            }
         }
      }
      foreach ($actorRules as $actorRule) {
          $renderedRuleKeys[] = $actorRule->key;
      }

       return [
           'field_rules'          => $fieldRules,
           'actor_rules'          => $actorRules,
           'actor_itemtype_roles' => self::actorItemtypeRoles(),
           'rendered_rule_keys'   => array_values(array_unique($renderedRuleKeys)),
       ];
   }

    /** @return list<array<string, mixed>> */
   private static function fieldRules(PolicyCatalog $catalog): array {
       $definitions = [
           ['key' => 'opening_date', 'label' => __('Opening date', 'torah')],
           ['key' => 'type', 'label' => __('Type', 'torah')],
           ['key' => 'category', 'label' => __('Category', 'torah')],
           ['key' => 'status', 'label' => __('Status', 'torah'), 'sensitive' => true],
           ['key' => 'request_source', 'label' => __('Request source', 'torah')],
           ['key' => 'urgency', 'label' => __('Urgency', 'torah')],
           ['key' => 'impact', 'label' => __('Impact', 'torah')],
           ['key' => 'priority', 'label' => __('Priority', 'torah'), 'sensitive' => true],
           ['key' => 'total_duration', 'label' => __('Total duration', 'torah')],
           ['key' => 'approval_status', 'label' => __('Approval request', 'torah')],
           ['key' => 'requester', 'label' => __('Requester', 'torah'), 'ui_only' => true],
           ['key' => 'observer', 'label' => __('Observer', 'torah'), 'ui_only' => true],
           ['key' => 'assigned_to', 'label' => __('Assigned to', 'torah'), 'ui_only' => true],
           ['key' => 'associated_item', 'label' => __('Associated items', 'torah')],
           ['key' => 'sla_tto', 'label' => __('TTO', 'torah'), 'sensitive' => true],
           ['key' => 'sla_ttr', 'label' => __('TTR', 'torah'), 'sensitive' => true],
           ['key' => 'ola_tto', 'label' => __('Internal TTO', 'torah'), 'sensitive' => true],
           ['key' => 'ola_ttr', 'label' => __('Internal TTR', 'torah'), 'sensitive' => true],
           ['key' => 'linked_tickets', 'label' => __('Linked tickets', 'torah'), 'ui_only' => true],
       ];

       $rules = [];
       foreach ($definitions as $definition) {
          $addKey = sprintf('ticket.field.%s.add', $definition['key']);
          $updateKey = sprintf('ticket.field.%s.update', $definition['key']);
          $rules[] = [
              'key'        => $definition['key'],
              'label'      => $definition['label'],
              'add_key'    => $catalog->has($addKey) ? $addKey : null,
              'update_key' => $catalog->has($updateKey) ? $updateKey : null,
              'ui_only'    => $definition['ui_only'] ?? false,
              'sensitive'  => $definition['sensitive'] ?? false,
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
}
