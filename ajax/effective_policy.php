<?php

include('../../../inc/includes.php');

use GlpiPlugin\Torah\Application\ActorItemtypePolicy;
use GlpiPlugin\Torah\Infrastructure\Glpi\AuthorizationContextFactory;
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

$context = (new AuthorizationContextFactory())->fromTicket($ticket);
$policy = $context === null ? null : ServiceFactory::resolver()->resolve($context);
$actorItemtypes = [];
if ($policy !== null) {
   foreach (array_keys(ActorItemtypePolicy::roleLabels()) as $role) {
       $actorItemtypes[$role] = ActorItemtypePolicy::allowedFor($policy, $role);
   }
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'blocked_rules'   => $policy?->blockedRuleKeys() ?? [],
    'actor_itemtypes' => $actorItemtypes,
], JSON_THROW_ON_ERROR);
