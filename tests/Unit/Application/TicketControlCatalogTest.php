<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\TicketControlCatalog;
use PHPUnit\Framework\TestCase;

final class TicketControlCatalogTest extends TestCase
{
   public function testMatrixContainsTheNineteenSupportedControls(): void {
      self::assertCount(19, (new TicketControlCatalog())->all());
   }

   public function testLogicalControlKeysMapToTheRealGlpiInputNames(): void {
      $catalog = new TicketControlCatalog();
      $expected = [
          'opening_date' => 'date',
          'category' => 'itilcategories_id',
          'request_source' => 'requesttypes_id',
          'total_duration' => 'actiontime',
      ];

      foreach ($expected as $key => $inputName) {
         self::assertSame(['[name="' . $inputName . '"]'], $catalog->get($key)?->selectors);
      }
   }
}
