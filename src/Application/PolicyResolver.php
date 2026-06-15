<?php

namespace GlpiPlugin\Torah\Application;

use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use GlpiPlugin\Torah\Domain\Authorization\AuthorizationDecision;
use GlpiPlugin\Torah\Domain\Policy\EntityHierarchy;
use GlpiPlugin\Torah\Domain\Policy\PolicyRepository;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;

final class PolicyResolver
{
   public function __construct(
        private readonly PolicyRepository $repository,
        private readonly EntityHierarchy $entityHierarchy,
    ) {
   }

   public function resolve(AuthorizationContext $context): ?PolicySet {
       $exact = $this->repository->findExact($context->profileId, $context->entityId);
      if ($exact !== null) {
          return $exact;
      }

       return $this->repository->findNearestRecursive(
           $context->profileId,
           $this->entityHierarchy->ancestorsOf($context->entityId),
       );
   }

   public function decide(AuthorizationContext $context, string $ruleKey): AuthorizationDecision {
       $policy = $this->resolve($context);
      if ($policy === null || !$policy->blocks($ruleKey)) {
          return AuthorizationDecision::allow();
      }

       return AuthorizationDecision::deny($ruleKey, $policy->id);
   }
}
