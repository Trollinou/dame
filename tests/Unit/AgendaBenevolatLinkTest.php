<?php
/**
 * Unit tests for Agenda and Benevolat linking DTO logic.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\DTO\AgendaEventDTO;
use DAME\Enums\CompetitionType;

/**
 * Class AgendaBenevolatLinkTest.
 */
final class AgendaBenevolatLinkTest extends TestCase {

	/**
	 * Test that AgendaEventDTO correctly holds and exports linked_benevolat_id.
	 */
	public function test_agenda_event_dto_with_linked_benevolat(): void {
		$dto = new AgendaEventDTO(
			id: 101,
			title: 'Tournoi de Noël',
			start_date: '2026-12-20',
			end_date: '2026-12-20',
			all_day: true,
			competition_type: CompetitionType::INDIVIDUAL,
			linked_benevolat_id: 55
		);

		$this->assertSame( 101, $dto->id );
		$this->assertSame( 55, $dto->linked_benevolat_id );

		$array = $dto->to_array();
		$this->assertArrayHasKey( 'linked_benevolat_id', $array );
		$this->assertSame( 55, $array['linked_benevolat_id'] );
	}

	/**
	 * Test that AgendaEventDTO defaults linked_benevolat_id to null when not specified.
	 */
	public function test_agenda_event_dto_default_null_linked_benevolat(): void {
		$dto = new AgendaEventDTO(
			id: 102,
			title: 'Stage d\'échecs',
			start_date: '2026-12-22',
			end_date: '2026-12-23',
			all_day: false
		);

		$this->assertNull( $dto->linked_benevolat_id );
		$array = $dto->to_array();
		$this->assertArrayHasKey( 'linked_benevolat_id', $array );
		$this->assertNull( $array['linked_benevolat_id'] );
	}
}

