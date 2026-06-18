<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;
use Plugin;
use Profile;

final class AdminPage
{
   public static function render(): void {
       $catalog = ServiceFactory::catalog();
       $policyModel = self::policyModel($catalog);

       $sets = [];
      foreach ((new GlpiPolicyStore())->all() as $set) {
          $sets[] = self::viewModel($set);
      }

       TemplateRenderer::getInstance()->display('@torah/admin/policies.html.twig', [
           'action'              => Plugin::getWebDir('torah') . '/front/policy.form.php',
           'policy_model'        => $policyModel,
           'new_actor_itemtypes' => self::actorItemtypes(null),
           'sets'                => $sets,
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
   private static function viewModel(PolicySet $set): array {
       return [
           'id'              => $set->id,
           'profile_id'      => $set->profileId,
           'entity_id'       => $set->entityId,
           'profile_name'    => Dropdown::getDropdownName('glpi_profiles', $set->profileId),
           'entity_name'     => Dropdown::getDropdownName('glpi_entities', $set->entityId),
           'recursive'       => $set->recursive,
           'blocked_rules'   => array_fill_keys($set->blockedRuleKeys(), true),
           'actor_itemtypes' => self::actorItemtypes($set),
       ];
   }

    /** @return array<string, mixed> */
   private static function policyModel(\GlpiPlugin\Torah\Application\PolicyCatalog $catalog): array {
       $actorRules = [];
      foreach ($catalog->all() as $rule) {
         if ($rule->domain === 'assistance' && $rule->object === 'ticket' && $rule->group === 'actors') {
             $actorRules[] = $rule;
         }
      }

       $fieldRules = [];
      foreach ($catalog->ticketFields() as $field) {
          $fieldRules[] = [
              'label'      => $field->label,
              'add_key'    => sprintf('ticket.field.%s.add', $field->key),
              'update_key' => sprintf('ticket.field.%s.update', $field->key),
          ];
      }

       return [
           'domains' => [
               [
                   'key'     => 'assistance',
                   'label'   => __('Assistance', 'torah'),
                   'objects' => [
                       [
                           'key'                  => 'ticket',
                           'label'                => __('Tickets', 'torah'),
                           'actor_rules'          => $actorRules,
                           'actor_itemtype_roles' => self::actorItemtypeRoles(),
                           'field_rules'          => $fieldRules,
                       ],
                   ],
               ],
               ['key' => 'configuration', 'label' => __('Configuration', 'torah'), 'objects' => []],
               ['key' => 'administration', 'label' => __('Administration', 'torah'), 'objects' => []],
               ['key' => 'other', 'label' => __('Other', 'torah'), 'objects' => []],
           ],
       ];
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
