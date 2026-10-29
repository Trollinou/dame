<?php
/**
 * Unit tests for Mailing Recipient Personalization and Tags Detection.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Admin\Pages\Mailing\RecipientResolver;

/**
 * Class MailingRecipientPersonalizationTest
 */
final class MailingRecipientPersonalizationTest extends TestCase {

	/**
	 * Resolver instance.
	 *
	 * @var RecipientResolver
	 */
	private RecipientResolver $resolver;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->resolver = new RecipientResolver();
	}

	/**
	 * Tests detection of personalization tags.
	 */
	public function test_has_personalization_tags(): void {
		$this->assertTrue( $this->resolver->has_personalization_tags( 'Bonjour [PRENOM]', 'Votre inscription est validée.' ) );
		$this->assertTrue( $this->resolver->has_personalization_tags( 'Rappel', 'Bonjour [NOM], merci de votre paiement.' ) );
		$this->assertTrue( $this->resolver->has_personalization_tags( 'Information', 'Cher [CIVILITE], votre enfant a [AGE] ans.' ) );
		$this->assertFalse( $this->resolver->has_personalization_tags( 'Rappel général', 'Chers adhérents, le club sera fermé demain.' ) );
	}

	/**
	 * Tests personalizing message for a HelloAsso participant with extra metadata.
	 */
	public function test_personalize_message_for_helloasso_participant(): void {
		$recipient_data = array(
			'first_name' => 'Léo',
			'last_name'  => 'Dupont',
		);

		$result = $this->resolver->personalize_message(
			0,
			'parent@example.com',
			'Léo Dupont (Payeur : Stage)',
			'Information pour [PRENOM]',
			'<p>Bonjour [PRENOM] [NOM], nous confirmons votre venue.</p>',
			$recipient_data
		);

		$this->assertSame( 'Information pour Léo', $result['subject'] );
		$this->assertSame( '<p>Bonjour Léo DUPONT, nous confirmons votre venue.</p>', $result['content'] );
	}
}

