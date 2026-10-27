<?php
/**
 * Tests for HelloAsso Client.
 *
 * @package DAME\Tests\Unit
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\HelloAsso\Client;

/**
 * Class HelloAssoClientTest
 */
class HelloAssoClientTest extends TestCase {

	/**
	 * Test configuration validation.
	 */
	public function test_is_configured_logic(): void {
		// In tests without options set, is_configured should be false.
		$this->assertFalse( Client::is_configured() );
	}

	/**
	 * Test webhook token creation format.
	 */
	public function test_webhook_url_structure(): void {
		$url = Client::get_webhook_url();
		$this->assertStringContainsString( 'dame/v1/helloasso/webhook', $url );
		$this->assertStringContainsString( 'token=', $url );
	}
}

