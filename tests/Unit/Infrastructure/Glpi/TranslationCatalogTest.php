<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Infrastructure\Glpi;

use PHPUnit\Framework\TestCase;

final class TranslationCatalogTest extends TestCase
{
   public function testNativeTicketTermsUseOfficialGlpiPortuguese(): void {
      $catalog = (string) file_get_contents(dirname(__DIR__, 4) . '/locales/pt_BR.po');
      $expected = [
          'Requester' => 'Requerente',
          'Approval request' => 'Requisição de aprovação',
          'Assigned to' => 'Atribuído',
          'TTO' => 'Tempo para atendimento',
          'TTR' => 'Tempo para solução',
          'Internal TTO' => 'Tempo interno para atendimento',
          'Internal TTR' => 'Tempo interno para solução',
          'Time to resolve' => 'Tempo para solução',
          'Internal time to resolve' => 'Tempo interno para solução',
          'Linked tickets' => 'Chamados relacionados',
      ];

      foreach ($expected as $message => $translation) {
         self::assertStringContainsString(
             sprintf("msgid \"%s\"\nmsgstr \"%s\"", $message, $translation),
             $catalog,
         );
      }
   }
}
