/**
 * Utilitaire de fetch avec timeout pour éviter de bloquer l'interface
 * et limiter les erreurs console en cas de serveur inaccessible.
 * @param url
 * @param options
 * @param timeout
 */
export const safeFetch = async (
	url: string,
	options: RequestInit = {},
	timeout = 5000
) => {
	const controller = new AbortController();
	const id = setTimeout( () => controller.abort(), timeout );

	try {
		const response = await fetch( url, {
			...options,
			signal: controller.signal,
		} );
		clearTimeout( id );
		return response;
	} catch ( error: any ) {
		clearTimeout( id );
		if ( error.name === 'AbortError' ) {
			throw new Error( 'Le serveur met trop de temps à répondre.' );
		}
		throw error;
	}
};
