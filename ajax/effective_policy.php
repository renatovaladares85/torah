<?php

include('../../../inc/includes.php');

use GlpiPlugin\Torah\Infrastructure\Glpi\AuthorizationContextFactory;
use GlpiPlugin\Torah\Infrastructure\Glpi\ServiceFactory;

Session::checkLoginUser();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

Session::checkCSRF($_POST);

$ticketId = (int) ($_POST['tickets_id'] ?? 0);
$ticket = new Ticket();
if ($ticketId <= 0 || !$ticket->getFromDB($ticketId) || !$ticket->can($ticketId, READ)) {
    http_response_code(404);
    exit;
}

$context = (new AuthorizationContextFactory())->fromTicket($ticket);
$policy = $context === null ? null : ServiceFactory::resolver()->resolve($context);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'blocked_rules' => $policy?->blockedRuleKeys() ?? [],
], JSON_THROW_ON_ERROR);
