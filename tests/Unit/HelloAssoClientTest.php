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

	/**
	 * Test environment URL resolution.
	 */
	public function test_environment_url_resolution(): void {
		global $dame_test_options;

		$dame_test_options['dame_options'] = array( 'helloasso_environment' => Client::ENV_PRODUCTION );
		$this->assertSame( 'https://api.helloasso.com/oauth2/token', Client::get_oauth_url() );
		$this->assertSame( 'https://api.helloasso.com/v5', Client::get_api_base_url() );

		$dame_test_options['dame_options'] = array( 'helloasso_environment' => Client::ENV_SANDBOX );
		$this->assertSame( 'https://api.helloasso-sandbox.com/oauth2/token', Client::get_oauth_url() );
		$this->assertSame( 'https://api.helloasso-sandbox.com/v5', Client::get_api_base_url() );
	}

	/**
	 * Test slug normalization.
	 */
	public function test_clean_org_slug(): void {
		$this->assertSame( 'mon-club', Client::clean_org_slug( 'mon-club' ) );
		$this->assertSame( 'mon-club', Client::clean_org_slug( 'MON-CLUB' ) );
		$this->assertSame( 'mon-club', Client::clean_org_slug( '  mon-club/  ' ) );
		$this->assertSame( 'mon-club', Client::clean_org_slug( 'https://www.helloasso.com/associations/mon-club' ) );
		$this->assertSame( 'mon-club', Client::clean_org_slug( 'https://www.helloasso.com/associations/mon-club/' ) );
		$this->assertSame( 'mon-club', Client::clean_org_slug( 'helloasso.com/associations/mon-club' ) );
	}
}

