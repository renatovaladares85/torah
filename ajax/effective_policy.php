<?php

include('../../../inc/includes.php');

use GlpiPlugin\Torah\Infrastructure\Glpi\ServiceFactory;

Session::checkLoginUser();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

$ticketId = (int) ($_POST['tickets_id'] ?? 0);
$ticket = new Ticket();
if ($ticketId <= 0 || !$ticket->getFromDB($ticketId) || !$ticket->can($ticketId, READ)) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(ServiceFactory::policyPayload()->forTicket($ticket, [], 'update'), JSON_THROW_ON_ERROR);
