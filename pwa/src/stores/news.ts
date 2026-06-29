import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { safeFetch } from '@/utils/safeFetch';
import { useQuery, useQueryClient } from '@tanstack/vue-query';

export interface Post {
	id: number;
	date: string;
	modified: string;
	title: {
		rendered: string;
	};
	content: {
		rendered: string;
	};
	excerpt: {
		rendered: string;
	};
	_embedded?: {
		'wp:featuredmedia'?: Array< {
			source_url: string;
		} >;
	};
}

export const useNewsStore = defineStore( 'news', () => {
	const queryClient = useQueryClient();

	const isCustomLoading = ref( false );
	const customPosts = ref< Post[] | null >( null );

	// Liste des actualités (Clé de cache public)
	const {
		data: queryPosts,
		isLoading: isQueryLoading,
		refetch,
	} = useQuery< Post[] >( {
		queryKey: [ 'news', 'list' ],
		queryFn: async () => {
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const response = await safeFetch(
				`${ apiUrl }/wp/v2/posts?_embed&per_page=20`,
				{},
				4000
			);

			if ( ! response.ok ) {
				throw new Error( 'Impossible de charger les actualités.' );
			}
			return response.json();
		},
	} );

	const posts = computed( {
		get: () =>
			customPosts.value !== null
				? customPosts.value
				: queryPosts.value || [],
		set: ( val ) => {
			customPosts.value = val;
		},
	} );

	const isLoading = computed( {
		get: () => isQueryLoading.value || isCustomLoading.value,
		set: ( val ) => {
			isCustomLoading.value = val;
		},
	} );

	const fetchPosts = async ( force = false ) => {
		if ( force ) {
			await queryClient.invalidateQueries( {
				queryKey: [ 'news', 'list' ],
			} );
		} else {
			await refetch();
		}
	};

	/**
	 * Sauvegarde ou met à jour un article unique directement dans le cache de TanStack Query
	 * @param post
	 */
	const savePost = ( post: Post ) => {
		queryClient.setQueryData< Post[] >( [ 'news', 'list' ], ( old ) => {
			const currentList = old || [];
			const index = currentList.findIndex( ( p ) => p.id === post.id );
			if ( index !== -1 ) {
				const copy = [ ...currentList ];
				copy[ index ] = post;
				return copy;
			}
			return [ post, ...currentList ];
		} );
	};

	const getPostById = ( id: number ): Post | undefined => {
		const list =
			queryClient.getQueryData< Post[] >( [ 'news', 'list' ] ) || [];
		return list.find( ( p ) => p.id === id );
	};

	const clearData = () => {
		customPosts.value = null;
		queryClient.setQueryData( [ 'news', 'list' ], [] );
	};

	return {
		posts,
		isLoading,
		fetchPosts,
		savePost,
		getPostById,
		clearData,
	};
} );
