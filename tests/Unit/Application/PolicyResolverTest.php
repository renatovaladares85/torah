<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\PolicyResolver;
use GlpiPlugin\Torah\Domain\Authorization\AuthorizationContext;
use GlpiPlugin\Torah\Domain\Policy\EntityHierarchy;
use GlpiPlugin\Torah\Domain\Policy\PolicyRepository;
use GlpiPlugin\Torah\Domain\Policy\PolicySet;
use PHPUnit\Framework\TestCase;

final class PolicyResolverTest extends TestCase
{
   public function testProfileWithoutConfigurationDoesNotInterfere(): void {
       $resolver = $this->resolver([], [20 => [10, 0]]);

       self::assertTrue($resolver->decide($this->context(profileId: 7), 'ticket.field.status')->allowed);
   }

   public function testExactEmptyPolicyStopsRecursiveInheritance(): void {
       $resolver = $this->resolver([
           $this->set(1, 7, 20, false, []),
           $this->set(2, 7, 10, true, ['ticket.field.status']),
       ], [20 => [10, 0]]);

       self::assertTrue($resolver->decide($this->context(), 'ticket.field.status')->allowed);
   }

   public function testExactPolicyHasPriorityOverRecursiveAncestor(): void {
       $resolver = $this->resolver([
           $this->set(1, 7, 20, false, ['ticket.field.type']),
           $this->set(2, 7, 10, true, ['ticket.field.status']),
       ], [20 => [10, 0]]);

       self::assertFalse($resolver->decide($this->context(), 'ticket.field.type')->allowed);
       self::assertTrue($resolver->decide($this->context(), 'ticket.field.status')->allowed);
   }

   public function testNearestRecursiveAncestorWinsWithoutMerging(): void {
       $resolver = $this->resolver([
           $this->set(1, 7, 10, true, ['ticket.field.status']),
           $this->set(2, 7, 0, true, ['ticket.field.type']),
       ], [20 => [10, 0]]);

       self::assertFalse($resolver->decide($this->context(), 'ticket.field.status')->allowed);
       self::assertTrue($resolver->decide($this->context(), 'ticket.field.type')->allowed);
   }

   public function testPoliciesAreIsolatedByActiveProfile(): void {
       $resolver = $this->resolver([
           $this->set(1, 8, 20, false, ['ticket.field.status']),
       ], [20 => [10, 0]]);

       self::assertTrue($resolver->decide($this->context(profileId: 7), 'ticket.field.status')->allowed);
       self::assertFalse($resolver->decide($this->context(profileId: 8), 'ticket.field.status')->allowed);
   }

    /**
     * @param list<PolicySet> $sets
     * @param array<int, list<int>> $hierarchy
     */
   private function resolver(array $sets, array $hierarchy): PolicyResolver {
       $repository = new class ($sets) implements PolicyRepository {
           /** @param list<PolicySet> $sets */
         public function __construct(private readonly array $sets) {
         }

         public function findExact(int $profileId, int $entityId): ?PolicySet {
            foreach ($this->sets as $set) {
               if ($set->profileId === $profileId && $set->entityId === $entityId) {
                  return $set;
               }
            }

             return null;
         }

         public function findNearestRecursive(int $profileId, array $ancestorEntityIds): ?PolicySet {
            foreach ($ancestorEntityIds as $entityId) {
               foreach ($this->sets as $set) {
                  if ($set->profileId === $profileId && $set->entityId === $entityId && $set->recursive) {
                         return $set;
                  }
               }
            }

             return null;
         }
       };

         $entityHierarchy = new class ($hierarchy) implements EntityHierarchy {
            /** @param array<int, list<int>> $hierarchy */
            public function __construct(private readonly array $hierarchy) {
            }

            public function ancestorsOf(int $entityId): array {
                return $this->hierarchy[$entityId] ?? [];
            }
         };

         return new PolicyResolver($repository, $entityHierarchy);
   }

    /** @param list<string> $rules */
   private function set(int $id, int $profileId, int $entityId, bool $recursive, array $rules): PolicySet {
       return new PolicySet($id, $profileId, $entityId, $recursive, $rules);
   }

   private function context(int $profileId = 7): AuthorizationContext {
       return new AuthorizationContext($profileId, 20, 100, 9);
   }
}
