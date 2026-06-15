<?php

/**
 * -------------------------------------------------------------------------
 * Torah plugin for GLPI
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Torah\Infrastructure\Glpi\DatabaseInstaller;

function plugin_torah_install(): bool {
    return DatabaseInstaller::install(PLUGIN_TORAH_VERSION);
}

function plugin_torah_uninstall(): bool {
    return DatabaseInstaller::uninstall();
}
