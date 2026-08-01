<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\TicketMutationGuard;
use PHPUnit\Framework\TestCase;

final class TicketMutationGuardMessageTest extends TestCase
{
   public function testFormatsSingleAndMultipleFriendlyControlLabels(): void {
      $guard = (new \ReflectionClass(TicketMutationGuard::class))->newInstanceWithoutConstructor();
      $method = new \ReflectionMethod(TicketMutationGuard::class, 'messageForLabels');

      self::assertSame(
          'Torah blocked the field "Opening date" according to the active profile and entity policy.',
          $method->invoke($guard, ['Opening date']),
      );
      self::assertSame(
          'Torah blocked the fields "TTO" and "Resolution date" according to the active profile and entity policy.',
          $method->invoke($guard, ['TTO', 'Resolution date']),
      );
   }
}
