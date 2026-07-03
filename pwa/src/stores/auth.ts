import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { toastController, alertController } from '@ionic/vue';
import { SimpleJwtLogin } from 'simple-jwt-login';
import { App } from '@capacitor/app';
import { queryClient } from '../queryClient';
import router from '../router';

// Import des autres stores pour nettoyage
import { useAgendaStore } from './agenda';
import { useContactStore } from './contacts';
import { useDashboardStore } from './dashboard';
import { useMemberStore } from './members';
import { useMessageStore } from './messages';
import { useBenevolatStore } from './benevolat';
import { useTournamentStore } from './tournament';
import { useNewsStore } from './news';
import { useApprentissageStore } from './apprentissage';

export interface AssociatedMember {
	firstname: string;
	name?: string;
	member_id: number;
	elo_standard?: number | string;
	elo_rapide?: number | string;
	elo_blitz?: number | string;
	already_registered?: boolean;
}

export interface Identity {
	id: string;
	name: string;
	type: 'member' | 'representative' | 'admin';
	member_id: number;
	firstname?: string;
	elo_standard?: number | string;
	elo_rapide?: number | string;
	elo_blitz?: number | string;
	associated_members?: AssociatedMember[];
	already_registered?: boolean;
}

export const useAuthStore = defineStore(
	'auth',
	() => {
		const isLoading = ref( false );

		const getStoredToken = () => {
			const t = localStorage.getItem( 'dame_jwt_token' );
			return t === 'null' || t === 'undefined' || ! t ? '' : t;
		};

		const getStoredUser = () => {
			const u = localStorage.getItem( 'dame_user' );
			try {
				return u === 'null' || u === 'undefined' || ! u
					? null
					: JSON.parse( u );
			} catch {
				return null;
			}
		};

		const getStoredIdentity = () => {
			const i = localStorage.getItem( 'dame_selected_identity' );
			try {
				return i === 'null' || i === 'undefined' || ! i
					? null
					: JSON.parse( i );
			} catch {
				return null;
			}
		};

		const token = ref( getStoredToken() );
		const user = ref< any >( getStoredUser() );
		const selectedIdentity = ref< Identity | null >( getStoredIdentity() );
		const adminMode = ref( false );

		const isAuthenticated = computed(
			() => !! token.value && token.value.length > 10
		);

		const userRoles = computed( () => {
			const roles = user.value?.roles;
			if ( Array.isArray( roles ) ) {
				return roles;
			}
			if ( typeof roles === 'object' && roles !== null ) {
				return Object.values( roles );
			}
			return [];
		} );

		const isAdmin = computed( () => {
			if ( ! isAuthenticated.value ) {
				return false;
			}
			const roles = userRoles.value;
			const privilegedRoles = [
				'administrator',
				'editor',
				'staff',
				'entraineur',
			];

			// Détection ultra-souple
			return roles.some( ( role ) => {
				if ( typeof role !== 'string' ) {
					return false;
				}
				return privilegedRoles.includes( role.toLowerCase() );
			} );
		} );

		const isAdherent = computed( () => {
			if ( ! isAuthenticated.value ) {
				return false;
			}
			return selectedIdentity.value?.type === 'member';
		} );

		const selectIdentity = ( identity: Identity ) => {
			selectedIdentity.value = identity;
			localStorage.setItem(
				'dame_selected_identity',
				JSON.stringify( identity )
			);
		};

		const getSiteRootUrl = () => {
			const url = import.meta.env.VITE_API_BASE_URL || '';
			return url.replace( /\/wp-json\/?$/, '' );
		};

		const callSdk = async (
			method:
				| 'authenticate'
				| 'validateToken'
				| 'revokeToken'
				| 'refreshToken',
			params: any
		): Promise< any > => {
			return new Promise( ( resolve, reject ) => {
				const client = new SimpleJwtLogin( getSiteRootUrl() );
				client.withCallback( ( response: any, status: number ) => {
					if ( status === 200 || status === 201 ) {
						resolve( response );
					} else {
						reject( response );
					}
				} );
				client[ method ]( params );
			} );
		};

		const tryRefreshToken = async () => {
			if ( ! token.value ) {
				logout();
				return;
			}
			try {
				console.log( 'Attempting to refresh token...' );
				const response = await callSdk( 'refreshToken', {
					JWT: token.value,
				} );
				const newJwtToken =
					response?.jwt || ( response?.data && response?.data?.jwt );
				if ( newJwtToken ) {
					token.value = newJwtToken;
					localStorage.setItem( 'dame_jwt_token', token.value );
					console.log( 'Token refreshed successfully.' );
				} else {
					logout();
				}
			} catch ( refreshError ) {
				console.warn( 'Token refresh failed:', refreshError );
				logout();
			}
		};

		const validateSession = async () => {
			if ( ! token.value ) {
				return;
			}
			try {
				const response = await callSdk( 'validateToken', {
					JWT: token.value,
				} );
				if ( response && response.success === false ) {
					await tryRefreshToken();
				}
			} catch ( error ) {
				console.warn( 'Session validation failed:', error );
				await tryRefreshToken();
			}
		};

		const translateErrorMessage = ( msg: string ): string => {
			if ( ! msg ) {
				return 'Erreur de connexion.';
			}
			const lowerMsg = msg.toLowerCase().trim();

			const translations: { [ key: string ]: string } = {
				'wrong user credentials.': 'Identifiants incorrects.',
				'wrong username or password.': 'Identifiants incorrects.',
				'wrong email or password.': 'Identifiants incorrects.',
				'user not found.': 'Utilisateur non trouvé.',
				'missing username or email.':
					"Nom d'utilisateur ou e-mail manquant.",
				'missing password.': 'Mot de passe manquant.',
				'token is expired.':
					'Votre session a expiré. Veuillez vous reconnecter.',
				'jwt is expired.':
					'Votre session a expiré. Veuillez vous reconnecter.',
				'invalid token.': 'Session de connexion invalide.',
				'jwt is invalid.': 'Session de connexion invalide.',
				'token has been revoked.':
					'Votre session a été fermée sur le serveur.',
				'validation failed.': 'Échec de la validation de session.',
			};

			if ( translations[ lowerMsg ] ) {
				return translations[ lowerMsg ];
			}

			if (
				lowerMsg.includes( 'credential' ) ||
				lowerMsg.includes( 'wrong password' ) ||
				lowerMsg.includes( 'incorrect' )
			) {
				return 'Identifiants incorrects.';
			}
			if ( lowerMsg.includes( 'expired' ) ) {
				return 'Votre session a expiré. Veuillez vous reconnecter.';
			}
			if ( lowerMsg.includes( 'invalid' ) ) {
				return 'Session invalide. Veuillez vous reconnecter.';
			}
			if ( lowerMsg.includes( 'not found' ) ) {
				return 'Utilisateur non trouvé.';
			}

			return msg;
		};

		const login = async ( username: string, password: string ) => {
			if ( ! username || ! password ) {
				return;
			}
			isLoading.value = true;

			try {
				const base64Password = btoa(
					unescape( encodeURIComponent( password ) )
				);
				const authParams: any = { password: base64Password };
				if ( username.includes( '@' ) ) {
					authParams.email = username;
				} else {
					authParams.username = username;
				}

				const data = await callSdk( 'authenticate', authParams );

				const jwtToken = data.jwt || ( data.data && data.data.jwt );

				if ( jwtToken ) {
					token.value = jwtToken;
					localStorage.setItem( 'dame_jwt_token', token.value );

					// Récupérer le profil complet via l'API WordPress standard
					let roles: string[] = [];
					let displayName = username;
					let email = '';

					try {
						const profileRes = await fetch(
							`${
								import.meta.env.VITE_API_BASE_URL
							}/wp/v2/users/me?context=edit`,
							{
								headers: {
									Authorization: `Bearer ${ token.value }`,
								},
							}
						);

						if ( profileRes.ok ) {
							const profile = await profileRes.json();
							if ( profile.roles ) {
								roles = profile.roles;
							}
							if ( profile.name ) {
								displayName = profile.name;
							}
							if ( profile.email ) {
								email = profile.email;
							}

							// Bloquer la connexion si l'utilisateur a uniquement le rôle "subscriber" (e-mail non validé)
							if (
								roles.length === 1 &&
								roles.includes( 'subscriber' )
							) {
								await callSdk( 'revokeToken', {
									JWT: token.value,
								} ).catch( () => {} );
								token.value = '';
								localStorage.removeItem( 'dame_jwt_token' );
								throw new Error(
									'Veuillez valider votre adresse e-mail avant de vous connecter.'
								);
							}
						}
					} catch ( e: any ) {
						if (
							e.message &&
							e.message.includes( 'Veuillez valider' )
						) {
							throw e;
						}
						console.warn(
							"Profil complet non accessible, utilisation des données d'identifiants."
						);
					}

					user.value = {
						name: displayName,
						email,
						roles,
					};

					localStorage.setItem(
						'dame_user',
						JSON.stringify( user.value )
					);

					// 2. Vérification des identités (familles)
					await checkIdentities( token.value );
				} else {
					throw new Error(
						data.message ||
							( data.data && data.data.message ) ||
							"Erreur d'identifiants"
					);
				}
			} catch ( error: any ) {
				console.error( 'Erreur de connexion:', error );

				let errorMessage = 'Erreur serveur.';
				if ( error && error.response ) {
					try {
						const parsed = JSON.parse( error.response );
						errorMessage =
							parsed.message ||
							( parsed.data && parsed.data.message ) ||
							errorMessage;
					} catch {
						errorMessage = error.response || errorMessage;
					}
				} else if ( error && error.message ) {
					errorMessage = error.message;
				}

				const alert = await alertController.create( {
					header: 'Échec de connexion',
					message: translateErrorMessage( errorMessage ),
					buttons: [ 'OK' ],
				} );
				await alert.present();
			} finally {
				isLoading.value = false;
			}
		};

		const checkIdentities = async ( jwtToken: string ) => {
			try {
				const response = await fetch(
					`${
						import.meta.env.VITE_API_BASE_URL
					}/dame/v1/my-identities`,
					{
						headers: { Authorization: `Bearer ${ jwtToken }` },
					}
				);

				if ( ! response.ok ) {
					throw new Error();
				}

				const identities: Identity[] = await response.json();

				if ( identities.length === 1 ) {
					selectIdentity( identities[ 0 ] );
					router.push( '/tabs/home' );
				} else if ( identities.length > 1 ) {
					router.push( '/tabs/select-person' );
				} else {
					const virtualIdentity: Identity = {
						id: 'wp_virtual',
						name: user.value?.name || 'Gestionnaire',
						type: 'member',
						member_id: 0,
					};
					selectIdentity( virtualIdentity );
					router.push( '/tabs/home' );
				}
			} catch ( error ) {
				router.push( '/tabs/home' );
			}
		};

		const logout = () => {
			if ( token.value ) {
				callSdk( 'revokeToken', { JWT: token.value } ).catch( ( e ) => {
					console.warn(
						'Erreur lors de la révocation du jeton sur le serveur:',
						e
					);
				} );
			}

			try {
				queryClient.clear(); // Vide le cache mémoire + le cache persistant LocalStorage de TanStack Query
			} catch ( e ) {
				console.warn(
					"Erreur lors de l'effacement du QueryClient:",
					e
				);
			}
			token.value = '';
			user.value = null;
			selectedIdentity.value = null;
			adminMode.value = false;
			localStorage.removeItem( 'dame_jwt_token' );
			localStorage.removeItem( 'dame_user' );
			localStorage.removeItem( 'dame_selected_identity' );
			useAgendaStore().clearData();
			useContactStore().clearData();
			useDashboardStore().clearData();
			useMemberStore().clearData();
			useMessageStore().clearData();
			useBenevolatStore().clearData();
			useTournamentStore().clearData();
			useNewsStore().clearData();
			useApprentissageStore().clearData();
			router.push( '/tabs/home' );
		};

		const isRoiActive = ref(
			localStorage.getItem( 'dame_roi_active' ) !== 'false'
		);
		const stockfishUrl = ref(
			localStorage.getItem( 'dame_stockfish_url' ) || ''
		);
		const wasmUrl = ref( localStorage.getItem( 'dame_wasm_url' ) || '' );
		const currentSeason = ref(
			localStorage.getItem( 'dame_current_season' ) || ''
		);

		const fetchPwaConfig = async () => {
			try {
				const response = await fetch(
					`${ import.meta.env.VITE_API_BASE_URL }/dame/v1/pwa-config`
				);
				if ( response.ok ) {
					const data = await response.json();
					isRoiActive.value = !! data.roi_active;
					stockfishUrl.value = data.stockfish_url || '';
					wasmUrl.value = data.wasm_url || '';
					currentSeason.value = data.current_season || '';
					localStorage.setItem(
						'dame_roi_active',
						String( isRoiActive.value )
					);
					localStorage.setItem(
						'dame_stockfish_url',
						stockfishUrl.value
					);
					localStorage.setItem( 'dame_wasm_url', wasmUrl.value );
					localStorage.setItem( 'dame_current_season', currentSeason.value );
				}
			} catch ( error ) {
				console.warn(
					'Erreur chargement pwa-config, utilisation du cache :',
					error
				);
			}
		};

		// Écoute du retour au premier plan (Foreground)
		try {
			App.addListener( 'appStateChange', ( { isActive } ) => {
				if ( isActive ) {
					validateSession();
				}
			} );
		} catch ( e ) {
			console.warn( 'Capacitor App listener non disponible :', e );
		}

		if ( typeof document !== 'undefined' ) {
			document.addEventListener( 'visibilitychange', () => {
				if ( document.visibilityState === 'visible' ) {
					validateSession();
				}
			} );
		}

		// Validation initiale au démarrage du store
		validateSession();

		return {
			token,
			user,
			selectedIdentity,
			adminMode,
			isAuthenticated,
			isAdmin,
			isAdherent,
			isLoading,
			login,
			logout,
			selectIdentity,
			checkIdentities,
			isRoiActive,
			stockfishUrl,
			wasmUrl,
			currentSeason,
			fetchPwaConfig,
			validateSession,
		};
	},
	{
		persist: {
			omit: [ 'isLoading' ],
		},
	}
);
