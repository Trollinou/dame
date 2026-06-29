import DOMPurify from 'dompurify';

/**
 * Configuration par défaut pour DOMPurify afin d'autoriser certains composants Ionic
 * et des attributs spécifiques utilisés pour les liens internes.
 */
const DEFAULT_CONFIG = {
	ADD_TAGS: [
		'ion-button',
		'ion-icon',
		'ion-badge',
		'ion-card',
		'ion-card-header',
		'ion-card-title',
		'ion-card-subtitle',
		'ion-card-content',
	],
	ADD_ATTR: [
		'expand',
		'href',
		'target',
		'slot',
		'color',
		'name',
		'fill',
		'data-path',
		'style', // data-path est utilisé pour useInternalLinks
	],
};

/**
 * Sanitizes a string of HTML to prevent XSS attacks.
 * @param html The raw HTML string to sanitize.
 * @return The sanitized HTML string.
 */
export const sanitizeHtml = ( html: string ): string => {
	if ( ! html ) {
		return '';
	}
	return DOMPurify.sanitize( html, DEFAULT_CONFIG );
};
