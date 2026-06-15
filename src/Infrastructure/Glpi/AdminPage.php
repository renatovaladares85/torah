<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;
use Plugin;
use Profile;

final class AdminPage
{
   public static function render(): void {
       $catalog = ServiceFactory::catalog();
       $groups = [
           'actors'     => ['label' => __('Actors', 'torah'), 'rules' => []],
           'properties' => ['label' => __('Properties', 'torah'), 'rules' => []],
           'external'   => ['label' => __('External actions', 'torah'), 'rules' => []],
       ];
       foreach ($catalog->all() as $rule) {
           $groups[$rule->group]['rules'][] = $rule;
       }

       $sets = [];
       foreach ((new GlpiPolicyStore())->all() as $set) {
           $sets[] = self::viewModel($set, $groups);
       }

       TemplateRenderer::getInstance()->display('@torah/admin/policies.html.twig', [
           'action'      => Plugin::getWebDir('torah') . '/front/policy.form.php',
           'groups'      => $groups,
           'sets'        => $sets,
           'new_profile' => Profile::dropdown([
               'name'    => 'profiles_id',
               'value'   => (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0),
               'display' => false,
           ]),
           'new_entity'  => Entity::dropdown([
               'name'    => 'entities_id',
               'value'   => (int) ($_SESSION['glpiactive_entity'] ?? 0),
               'display' => false,
           ]),
       ]);
   }

    /**
     * @param array<string, array{label: string, rules: array<int, mixed>}> $groups
     * @return array<string, mixed>
     */
   private static function viewModel(PolicySet $set, array $groups): array {
       return [
           'id'            => $set->id,
           'profile_id'    => $set->profileId,
           'entity_id'     => $set->entityId,
           'profile_name'  => Dropdown::getDropdownName('glpi_profiles', $set->profileId),
           'entity_name'   => Dropdown::getDropdownName('glpi_entities', $set->entityId),
           'recursive'     => $set->recursive,
           'blocked_rules' => array_fill_keys($set->blockedRuleKeys(), true),
           'groups'        => $groups,
       ];
   }
}
