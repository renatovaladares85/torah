<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Application\GlobalActorItemtypePolicy;
use GlpiPlugin\Torah\Application\TicketControlCatalog;

/** Converts ticket control definitions to Twig-safe administrative data. */
final class AdminPolicyModelBuilder
{
   /** @return array<string, mixed> */
   public function build(): array {
      $fieldRules = [];
      $renderedRuleKeys = [];
      foreach ((new TicketControlCatalog())->all() as $definition) {
         $fieldRules[] = [
             'key' => $definition->key,
             'label' => $definition->label,
             'add_keys' => $definition->addRuleKeys,
             'update_keys' => $definition->updateRuleKeys,
             'opening_applicable' => $definition->addRuleKeys !== [],
             'update_applicable' => $definition->updateRuleKeys !== [],
             'sensitive' => $definition->sensitive,
         ];
         $renderedRuleKeys = [...$renderedRuleKeys, ...$definition->addRuleKeys, ...$definition->updateRuleKeys];
      }

      $actorItemtypes = [];
      foreach (GlobalActorItemtypePolicy::itemtypeLabels() as $itemtype => $label) {
         $actorItemtypes[] = ['key' => $itemtype, 'label' => $label];
      }

      $roles = [];
      foreach (GlobalActorItemtypePolicy::roleLabels() as $role => $label) {
         $roles[] = ['key' => $role, 'label' => $label, 'itemtypes' => $actorItemtypes];
      }

      return [
          'field_rules' => $fieldRules,
          'actor_itemtypes' => $actorItemtypes,
          'actor_itemtype_roles' => $roles,
          'rendered_rule_keys' => array_values(array_unique($renderedRuleKeys)),
      ];
   }
}
