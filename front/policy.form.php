<?php

include('../../../inc/includes.php');

use GlpiPlugin\Torah\Application\Admin\DeletePolicySet;
use GlpiPlugin\Torah\Application\Admin\PolicySetInput;
use GlpiPlugin\Torah\Application\Admin\SavePolicySet;
use GlpiPlugin\Torah\Infrastructure\Glpi\GlpiPolicyStore;
use GlpiPlugin\Torah\Infrastructure\Glpi\ServiceFactory;

Session::checkRight('config', UPDATE);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

Session::checkCSRF($_POST);

try {
   $store = new GlpiPolicyStore();
    $catalog = ServiceFactory::catalog();
   if (isset($_POST['delete'])) {
       (new DeletePolicySet($store))->execute((int) ($_POST['id'] ?? 0));
       Session::addMessageAfterRedirect(__('Policy set removed.', 'torah'));
   } else {
       $input = PolicySetInput::fromHttp($_POST, $catalog);
       (new SavePolicySet($store, $catalog))->execute($input);
       Session::addMessageAfterRedirect(__('Policy set saved.', 'torah'));
   }
} catch (\Throwable $error) {
    Session::addMessageAfterRedirect(
        __('The policy set could not be saved. Review the selected scope and rules.', 'torah'),
        false,
        ERROR,
    );
}

Html::redirect(Plugin::getWebDir('torah') . '/front/config.php');
