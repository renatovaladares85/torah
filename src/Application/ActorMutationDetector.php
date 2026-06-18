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

    /**
     * @param array<string, mixed> $input
     * @return list<string>
     */
   public function addedItemtypes(Ticket $ticket, array $input, string $role): array {
      if (!isset(self::ROLES[$role])) {
          return [];
      }

       return $this->inspector->addedItemtypes(
           $input,
           $role,
           $ticket->getActorsForType(self::ROLES[$role]),
       );
   }

    /**
     * @param array<string, mixed> $input
     * @return list<string>
     */
   public function addedItemtypesForNewTicket(array $input, string $role): array {
       return $this->inspector->addedItemtypes($input, $role, []);
   }
}
