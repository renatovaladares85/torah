<?php

namespace GlpiPlugin\Torah\Application;

use CommonITILActor;
use Ticket;

final class ActorMutationDetector
{
   private const ROLES = [
        'requester' => CommonITILActor::REQUESTER,
        'observer'  => CommonITILActor::OBSERVER,
        'assign'    => CommonITILActor::ASSIGN,
    ];

   private readonly ActorPayloadInspector $inspector;

   public function __construct(?ActorPayloadInspector $inspector = null) {
       $this->inspector = $inspector ?? new ActorPayloadInspector();
   }

    /** @param array<string, mixed> $input */
   public function hasMutation(Ticket $ticket, array $input, string $role): bool {
      if (!isset(self::ROLES[$role])) {
          return false;
      }

       return $this->inspector->hasMutation(
           $input,
           $role,
           $ticket->getActorsForType(self::ROLES[$role]),
       );
   }
}
