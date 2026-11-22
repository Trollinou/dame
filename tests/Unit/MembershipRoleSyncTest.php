<?php
/**
 * Tests for Membership_Role_Sync.
 *
 * @package DAME\Tests\Unit
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\Membership_Role_Sync;

/**
 * Class MembershipRoleSyncTest
 */
class MembershipRoleSyncTest extends TestCase {

	/**
	 * Test that protected roles are never promoted or demoted.
	 */
	public function test_protected_roles_are_untouched(): void {
		foreach ( Membership_Role_Sync::PROTECTED_ROLES as $role ) {
			$this->assertNull(
				Membership_Role_Sync::resolve_target_role( array( $role ), true, false ),
				"Protected role {$role} must not be demoted."
			);
			$this->assertNull(
				Membership_Role_Sync::resolve_target_role( array( $role ), true, true ),
				"Protected role {$role} must not be changed."
			);
		}

		// User with both 'membre' and 'staff' must remain untouched.
		$this->assertNull(
			Membership_Role_Sync::resolve_target_role( array( 'membre', 'staff' ), true, false )
		);
	}

	/**
	 * Test that unverified subscribers are never promoted even with an active membership.
	 */
	public function test_unverified_subscriber_is_not_promoted(): void {
		$this->assertNull(
			Membership_Role_Sync::resolve_target_role( array( 'subscriber' ), false, true )
		);
	}

	/**
	 * Test that verified subscribers with an active membership are promoted to membre.
	 */
	public function test_verified_subscriber_with_active_membership_is_promoted(): void {
		$this->assertSame(
			'membre',
			Membership_Role_Sync::resolve_target_role( array( 'subscriber' ), true, true )
		);
	}

	/**
	 * Test that membres without an active membership are demoted to subscriber.
	 */
	public function test_membre_without_active_membership_is_demoted(): void {
		$this->assertSame(
			'subscriber',
			Membership_Role_Sync::resolve_target_role( array( 'membre' ), true, false )
		);
	}

	/**
	 * Test that membres with an active membership and subscribers without active membership remain unchanged.
	 */
	public function test_already_in_sync_roles_return_null(): void {
		$this->assertNull(
			Membership_Role_Sync::resolve_target_role( array( 'membre' ), true, true )
		);
		$this->assertNull(
			Membership_Role_Sync::resolve_target_role( array( 'subscriber' ), true, false )
		);
	}
}
