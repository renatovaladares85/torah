<?php

include('../../../inc/includes.php');

use GlpiPlugin\Torah\Application\Admin\SaveGlobalActorSettings;
use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiGlobalActorSettingsStore;

Session::checkRight('config', UPDATE);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

try {
    $settings = is_array($_POST['actor_itemtypes'] ?? null) ? $_POST['actor_itemtypes'] : [];
    (new SaveGlobalActorSettings(new GlpiGlobalActorSettingsStore()))->execute($settings);
    Session::addMessageAfterRedirect(__('Global actor settings saved.', 'torah'));
} catch (\Throwable) {
    Session::addMessageAfterRedirect(
        __('Global actor settings could not be saved. Select at least one type for each role.', 'torah'),
        false,
        ERROR,
    );
}

Html::redirect(Plugin::getWebDir('torah') . '/front/config.php');
