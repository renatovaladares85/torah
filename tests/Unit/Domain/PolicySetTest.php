<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Domain;

use GlpiPlugin\Torah\Domain\Policy\PolicySet;
use PHPUnit\Framework\TestCase;

final class PolicySetTest extends TestCase
{
   public function testRulesAreDeduplicatedAndUncheckedRulesAreAllowed(): void {
       $set = new PolicySet(1, 2, 3, false, [
           'ticket.field.status',
           'ticket.field.status',
       ]);

       self::assertSame(['ticket.field.status'], $set->blockedRuleKeys());
       self::assertTrue($set->blocks('ticket.field.status'));
       self::assertFalse($set->blocks('ticket.field.type'));
   }
}
