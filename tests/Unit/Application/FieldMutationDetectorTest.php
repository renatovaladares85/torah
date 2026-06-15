<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\CapabilityRegistry;
use GlpiPlugin\Torah\Application\FieldMutationDetector;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FieldMutationDetectorTest extends TestCase
{
    #[DataProvider('propertyInputKeys')]
   public function testEveryPropertyInputKeyDetectsAChangedValue(string $key): void {
       $detector = new FieldMutationDetector();
       $existing = $key === '_contracts_id' ? [] : [$key => '1'];

       self::assertTrue($detector->changes($existing, [$key => '2'], $key));
   }

    #[DataProvider('propertyInputKeys')]
   public function testEveryPropertyInputKeyIgnoresAnAbsentValue(string $key): void {
       self::assertFalse((new FieldMutationDetector())->changes([$key => '1'], [], $key));
   }

    /** @return iterable<string, array{string}> */
   public static function propertyInputKeys(): iterable {
       $catalog = new PolicyCatalog(new CapabilityRegistry());
      foreach ($catalog->all() as $rule) {
         if ($rule->group !== 'properties') {
            continue;
         }
         foreach ($rule->inputKeys as $key) {
             yield $rule->key => [$key];
         }
      }
   }
}
