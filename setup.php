<?php

/**
 * -------------------------------------------------------------------------
 * Torah plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * This file is part of Torah.
 *
 * Torah is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version.
 * -------------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Torah\Infrastructure\Glpi\HookBridge;

define('PLUGIN_TORAH_VERSION', '0.4.6');
define('PLUGIN_TORAH_MIN_GLPI_VERSION', '10.0.20');
define('PLUGIN_TORAH_MAX_GLPI_VERSION', '10.0.99');
define('PLUGIN_TORAH_MIN_PHP_VERSION', '8.2.0');

/**
 * Register plugin hooks.
 */
function plugin_init_torah(): void {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['torah'] = true;
    $PLUGIN_HOOKS['config_page']['torah'] = 'front/config.php';
    $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['torah'] = [HookBridge::class, 'postItemForm'];
    $PLUGIN_HOOKS[Hooks::FILTER_ACTORS]['torah'] = [HookBridge::class, 'filterActors'];

   foreach ([Ticket::class, Ticket_User::class, Group_Ticket::class, Supplier_Ticket::class, Ticket_Contract::class, Item_Ticket::class, Ticket_Ticket::class, TicketValidation::class] as $itemtype) {
       $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['torah'][$itemtype] = [HookBridge::class, 'preItemUpdate'];
   }

   foreach ([Ticket::class, Ticket_User::class, Group_Ticket::class, Supplier_Ticket::class, Ticket_Contract::class, Item_Ticket::class, Ticket_Ticket::class, TicketValidation::class] as $itemtype) {
       $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['torah'][$itemtype] = [HookBridge::class, 'preItemAdd'];
   }

   $PLUGIN_HOOKS[Hooks::ITEM_ADD]['torah'][Ticket::class] = [HookBridge::class, 'itemAdd'];

   foreach ([Ticket_User::class, Group_Ticket::class, Supplier_Ticket::class, Ticket_Contract::class, Item_Ticket::class, Ticket_Ticket::class, TicketValidation::class, SlaLevel_Ticket::class, OlaLevel_Ticket::class] as $itemtype) {
       $PLUGIN_HOOKS[Hooks::PRE_ITEM_DELETE]['torah'][$itemtype] = [HookBridge::class, 'preItemDelete'];
       $PLUGIN_HOOKS[Hooks::PRE_ITEM_PURGE]['torah'][$itemtype] = [HookBridge::class, 'preItemDelete'];
   }
}

/**
 * Return plugin metadata.
 *
 * @return array<string, mixed>
 */
function plugin_version_torah(): array {
    return [
        'name'         => 'Torah',
        'version'      => PLUGIN_TORAH_VERSION,
        'author'       => 'Torah contributors',
        'license'      => 'GPL-3.0-or-later',
        'homepage'     => 'https://github.com/renatovaladares85/torah',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_TORAH_MIN_GLPI_VERSION,
                'max' => PLUGIN_TORAH_MAX_GLPI_VERSION,
            ],
            'php' => [
                'min' => PLUGIN_TORAH_MIN_PHP_VERSION,
            ],
        ],
    ];
}

function plugin_torah_check_prerequisites(): bool {
    return version_compare(PHP_VERSION, PLUGIN_TORAH_MIN_PHP_VERSION, '>=');
}

function plugin_torah_check_config(bool $verbose = false): bool {
    return true;
}
