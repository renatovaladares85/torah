<?php

namespace GlpiPlugin\Torah\Domain\Authorization;

final class AuthorizationContext
{
   public function __construct(
        public readonly int $profileId,
        public readonly int $entityId,
        public readonly int $ticketId,
        public readonly int $userId,
    ) {
   }
}
