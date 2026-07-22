import { useAuthStore } from '@/stores/auth';

/**
 * Utilitaire de fetch avec timeout pour éviter de bloquer l'interface,
 * gestion transparente du rafraîchissement des jetons JWT en cas de statut 401
 * et retry automatique de la requête.
 *
 * @param url
 * @param options
 * @param timeout
 * @param isRetry
 */
export const safeFetch = async (
	url: string,
	options: RequestInit = {},
	timeout = 5000,
	isRetry = false
): Promise< Response > => {
	const controller = new AbortController();
	const id = setTimeout( () => controller.abort(), timeout );

	try {
		const response = await fetch( url, {
			...options,
			signal: controller.signal,
		} );
		clearTimeout( id );

		// En cas d'erreur 401 (Unauthorized), tentative de rafraîchissement transparent du token JWT
		if (
			response.status === 401 &&
			! isRetry &&
			typeof localStorage !== 'undefined' &&
			localStorage.getItem( 'dame_jwt_token' )
		) {
			try {
				const authStore = useAuthStore();
				const newToken = await authStore.tryRefreshToken();
				if ( newToken ) {
					// Mettre à jour le header Authorization avec le nouveau token
					const existingHeaders = ( options.headers || {} ) as Record<
						string,
						string
					>;
					const newHeaders: Record< string, string > = {
						...existingHeaders,
						Authorization: `Bearer ${ newToken }`,
					};
					// Re-jouer la requête de manière transparente avec le nouveau jeton
					return await safeFetch(
						url,
						{ ...options, headers: newHeaders },
						timeout,
						true
					);
				}
			} catch ( refreshError ) {
				console.warn(
					'Échec du rafraîchissement transparent du token :',
					refreshError
				);
			}
		}

		return response;
	} catch ( error: any ) {
		clearTimeout( id );
		if ( error.name === 'AbortError' ) {
			throw new Error( 'Le serveur met trop de temps à répondre.' );
		}
		throw error;
	}
};
