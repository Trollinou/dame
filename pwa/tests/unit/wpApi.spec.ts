import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest';
import { fetchWpCollection } from '@/utils/wpApi';
import * as safeFetchModule from '@/utils/safeFetch';

describe( 'wpApi utility', () => {
	beforeEach( () => {
		vi.restoreAllMocks();
		localStorage.clear();
	} );

	afterEach( () => {
		vi.restoreAllMocks();
	} );

	test( 'fetches single page collection and injects Authorization header if token exists', async () => {
		localStorage.setItem( 'dame_jwt_token', 'test-token-123' );

		const mockData = [
			{ id: 1, name: 'Item 1' },
			{ id: 2, name: 'Item 2' },
		];

		const safeFetchSpy = vi
			.spyOn( safeFetchModule, 'safeFetch' )
			.mockResolvedValue( {
				ok: true,
				status: 200,
				headers: new Headers( { 'X-WP-TotalPages': '1' } ),
				json: async () => mockData,
			} as any );

		const result = await fetchWpCollection< { id: number; name: string } >(
			'/wp/v2/items?per_page=100'
		);

		expect( result ).toEqual( mockData );
		expect( safeFetchSpy ).toHaveBeenCalledTimes( 1 );
		expect( safeFetchSpy.mock.calls[ 0 ][ 0 ] ).toContain( '/wp/v2/items?per_page=100&page=1' );

		const headers = safeFetchSpy.mock.calls[ 0 ][ 1 ]?.headers as Record< string, string >;
		expect( headers?.Authorization ).toBe( 'Bearer test-token-123' );
	} );

	test( 'fetches multi-page collection concurrently and merges results', async () => {
		localStorage.setItem( 'dame_jwt_token', 'test-token-multi' );

		const page1Data = [ { id: 1 }, { id: 2 } ];
		const page2Data = [ { id: 3 }, { id: 4 } ];

		const safeFetchSpy = vi
			.spyOn( safeFetchModule, 'safeFetch' )
			.mockImplementation( async ( url ) => {
				const urlStr = url as string;
				if ( /[?&]page=1(&|$)/.test( urlStr ) ) {
					return {
						ok: true,
						status: 200,
						headers: new Headers( { 'X-WP-TotalPages': '2' } ),
						json: async () => page1Data,
					} as any;
				}
				if ( /[?&]page=2(&|$)/.test( urlStr ) ) {
					return {
						ok: true,
						status: 200,
						headers: new Headers( { 'X-WP-TotalPages': '2' } ),
						json: async () => page2Data,
					} as any;
				}
				throw new Error( 'Unexpected URL: ' + urlStr );
			} );

		const result = await fetchWpCollection< { id: number } >(
			'/wp/v2/items?per_page=100'
		);

		expect( result ).toEqual( [ { id: 1 }, { id: 2 }, { id: 3 }, { id: 4 } ] );
		expect( safeFetchSpy ).toHaveBeenCalledTimes( 2 );
	} );

	test( 'throws descriptive error when main or sub-page request fails', async () => {
		vi.spyOn( safeFetchModule, 'safeFetch' ).mockResolvedValue( {
			ok: false,
			status: 500,
			statusText: 'Internal Server Error',
		} as any );

		await expect(
			fetchWpCollection( '/wp/v2/items' )
		).rejects.toThrow( 'Erreur API REST (500 Internal Server Error)' );
	} );
} );
