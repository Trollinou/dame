import { safeFetch } from '@/utils/safeFetch';

/**
 * Option d'options pour l'appel à l'API WP REST.
 */
export interface FetchWpCollectionOptions extends RequestInit {
	timeout?: number;
}

/**
 * Récupère une collection complète WP REST API en gérant automatiquement :
 * 1. L'injection du token JWT (`Authorization: Bearer <token>`) si présent.
 * 2. La détection et le téléchargement de l'ensemble des pages via le header `X-WP-TotalPages`.
 * 3. La gestion transparente du rafraîchissement des tokens via `safeFetch`.
 * 4. La validation stricte du statut HTTP (`res.ok`) sur TOUTES les pages.
 *
 * @param path Chemin relatif (ex: `/wp/v2/adherents?per_page=100`) ou URL complète.
 * @param options Options d'invalidation / headers complémentaires.
 * @returns Promesse de tableau contenant l'ensemble des éléments récoltés.
 */
export async function fetchWpCollection< T >(
	path: string,
	options: FetchWpCollectionOptions = {}
): Promise< T[] > {
	const { timeout = 5000, ...fetchOptions } = options;

	const apiBase = import.meta.env?.VITE_API_BASE_URL || '';
	const baseUrl = path.startsWith( 'http' )
		? path
		: `${ apiBase }${ path }`;

	// Récupération du jeton JWT actuel si disponible
	const token =
		typeof localStorage !== 'undefined'
			? localStorage.getItem( 'dame_jwt_token' )
			: null;

	const headers: Record< string, string > = {
		'Content-Type': 'application/json',
		...( ( fetchOptions.headers as Record< string, string > ) || {} ),
	};

	if ( token && ! headers.Authorization ) {
		headers.Authorization = `Bearer ${ token }`;
	}

	const requestOptions: RequestInit = {
		...fetchOptions,
		headers,
	};

	// 1. Première page (on vérifie avec regex pour éviter la confusion avec per_page)
	const hasPageParam = /[?&]page=\d+/.test( baseUrl );
	const page1Url = hasPageParam
		? baseUrl
		: `${ baseUrl }${ baseUrl.includes( '?' ) ? '&' : '?' }page=1`;

	const response = await safeFetch( page1Url, requestOptions, timeout );

	if ( ! response.ok ) {
		throw new Error(
			`Erreur API REST (${ response.status } ${ response.statusText }) sur ${ page1Url }`
		);
	}

	const totalPagesHeader = response.headers?.get?.( 'X-WP-TotalPages' );
	const totalPages = totalPagesHeader ? parseInt( totalPagesHeader, 10 ) : 1;

	let collection: T[] = await response.json();

	// 2. Si plusieurs pages existent, charger les pages 2..totalPages
	if ( totalPages > 1 ) {
		const cleanBaseUrl = baseUrl.replace( /([?&])page=\d+&?/, '$1' ).replace( /[?&]$/, '' );
		const separator = cleanBaseUrl.includes( '?' ) ? '&' : '?';

		const pagePromises = [];
		for ( let p = 2; p <= totalPages; p++ ) {
			const pageUrl = `${ cleanBaseUrl }${ separator }page=${ p }`;
			pagePromises.push(
				safeFetch( pageUrl, requestOptions, timeout ).then( async ( res ) => {
					if ( ! res.ok ) {
						throw new Error(
							`Erreur API REST page ${ p } (${ res.status } ${ res.statusText })`
						);
					}
					return ( await res.json() ) as T[];
				} )
			);
		}

		const pagesResults = await Promise.all( pagePromises );
		for ( const pageData of pagesResults ) {
			collection = collection.concat( pageData );
		}
	}

	return collection;
}
