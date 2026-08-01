<?php

declare(strict_types=1);

namespace GlpiPlugin\Torah\Tests\Unit\Application;

use GlpiPlugin\Torah\Application\TicketControlCatalog;
use PHPUnit\Framework\TestCase;

final class TicketControlCatalogTest extends TestCase
{
   public function testMatrixContainsTheTwentyOneSupportedControls(): void {
      self::assertCount(21, (new TicketControlCatalog())->all());
   }

   public function testLogicalControlKeysMapToTheRealGlpiInputNames(): void {
      $catalog = new TicketControlCatalog();
      $expected = [
          'opening_date' => 'date',
          'category' => 'itilcategories_id',
          'request_source' => 'requesttypes_id',
          'total_duration' => 'actiontime',
          'solution_date' => 'solvedate',
          'closing_date' => 'closedate',
      ];

      foreach ($expected as $key => $inputName) {
         self::assertSame(['[name="' . $inputName . '"]'], $catalog->get($key)?->selectors);
      }
   }

   public function testDateAndCompositeControlsDeclareTheirStableLockStrategies(): void {
      $catalog = new TicketControlCatalog();

      self::assertSame('flatpickr', $catalog->get('opening_date')?->lockStrategy);
      self::assertSame('flatpickr', $catalog->get('solution_date')?->lockStrategy);
      self::assertSame('flatpickr', $catalog->get('closing_date')?->lockStrategy);
      self::assertSame('composite', $catalog->get('tto')?->lockStrategy);
      self::assertSame([
          ['strategy' => 'flatpickr', 'selectors' => ['[name="time_to_own"]']],
          ['strategy' => 'select2', 'selectors' => ['[name="slas_id_tto"]']],
      ], $catalog->get('tto')?->controls);
      self::assertSame([], $catalog->get('solution_date')?->addRuleKeys);
      self::assertSame(['ticket.field.solution_date.update'], $catalog->get('solution_date')?->updateRuleKeys);
   }

   public function testGlpiTenDropdownControlsUseTheirSelect2WidgetStrategy(): void {
      $catalog = new TicketControlCatalog();

      foreach (['type', 'category', 'status', 'request_source', 'urgency', 'impact', 'priority', 'total_duration'] as $key) {
         self::assertSame('select2', $catalog->get($key)?->lockStrategy, $key);
      }
   }

   public function testActorControlsTargetTheGlpiSelect2AndAssociateMyselfButton(): void {
      $catalog = new TicketControlCatalog();
      $roles = [
         'requester' => 'requester',
         'observer' => 'observer',
         'assignee' => 'assign',
      ];

      foreach ($roles as $key => $role) {
         $control = $catalog->get($key);
         self::assertSame('actor', $control?->lockStrategy, $key);
         self::assertSame([
            '[data-actor-type="' . $role . '"]',
            'button[form^="addme_as_' . $role . '_"]',
         ], $control?->selectors, $key);
      }
   }

   public function testApprovalRequestLocksItsVisibleGlpiSelect2WithoutTargetingTheHiddenPayload(): void {
      $control = (new TicketControlCatalog())->get('approval_request');

      self::assertSame('composite', $control?->lockStrategy);
      self::assertSame([
         '[name="_add_validation"]',
         'select[name="validatortype"]',
         'select[name^="users_id_validate"]',
      ], $control?->selectors);
      self::assertSame([
         [
            'strategy' => 'select2',
            'selectors' => ['select[name="validatortype"]', 'select[name^="users_id_validate"]'],
         ],
      ], $control?->controls);
   }

   public function testRuleKeyResolvesToItsFriendlyControl(): void {
      $control = (new TicketControlCatalog())->findByRuleKey('ticket.field.sla_tto.update');

      self::assertSame('tto', $control?->key);
      self::assertSame('TTO', $control?->label);
   }
}
