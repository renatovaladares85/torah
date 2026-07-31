<?php

include('../../../inc/includes.php');

use GlpiPlugin\Torah\Infrastructure\Glpi\ServiceFactory;

Session::checkLoginUser();

$action = (string) ($_GET['action'] ?? '');
$ticketId = (int) ($_GET['tickets_id'] ?? 0);
$entityId = (int) ($_GET['entities_id'] ?? -1);
if (!in_array($action, ['add', 'update'], true) || $entityId < 0) {
   http_response_code(400);
   exit;
}

$ticket = new Ticket();
if ($ticketId > 0) {
   if (!$ticket->getFromDB($ticketId) || !$ticket->can($ticketId, READ)) {
      http_response_code(403);
      exit;
   }
   $entityId = (int) $ticket->fields['entities_id'];
} else if (!Session::haveAccessToEntity($entityId)) {
   http_response_code(403);
   exit;
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(ServiceFactory::policyPayload()->forTicket($ticket, ['entities_id' => $entityId], $action), JSON_THROW_ON_ERROR);
