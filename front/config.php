<?php

include('../../../inc/includes.php');

use GlpiPlugin\Torah\Infrastructure\Glpi\AdminPage;

Session::checkRight('config', UPDATE);

Html::header(__('Torah policies', 'torah'), $_SERVER['PHP_SELF'], 'config', 'plugins');
AdminPage::render();
Html::footer();
