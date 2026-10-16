const wordpress = require( '@wordpress/eslint-plugin' );

module.exports = [
	{
		ignores: [
			'node_modules/**',
			'vendor/**',
			'build/**',
			'dist/**',
			'jules-scratch/**',
			'assets/js/*.min.js',
		],
	},
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				// On déclare les outils globaux de WordPress et du navigateur
				jQuery: 'readonly',
				ajaxurl: 'readonly',
				confirm: 'readonly',
				alert: 'readonly',
				history: 'readonly',
				console: 'readonly',
				// On autorise vos objets de données localisés (les variables PHP passées au JS)
				dame_admin_data: 'readonly',
				dame_mailing_data: 'readonly',
				dame_agenda_ajax: 'readonly',
				dame_contact_ajax: 'readonly',
				dame_pre_inscription_ajax: 'readonly',
				dame_saisons_data: 'readonly',
				dame_test_send_data: 'readonly',
				dame_adherent_actions_data: 'readonly',
				dame_agenda_manager_data: 'readonly',
				dame_backup_adherent_data: 'readonly',
				dame_backup_agenda_data: 'readonly',
				dame_pre_inscription_actions_data: 'readonly',
				dame_settings_anniversaires: 'readonly',
				damePwaInstaller: 'readonly',
			},
		},
		rules: {
			// =================================================================
			// 1. RIGUEUR & QUALITÉ (Équivalent "Strict" JS/TS)
			// =================================================================
			'eqeqeq': [ 'error', 'smart' ], // Oblige === et !== (tolère == null)
			'curly': [ 'error', 'all' ], // Oblige les accolades {} même sur 1 ligne
			'prefer-const': 'error', // Impose const si la variable n'est pas réassignée
			'no-shadow': 'error', // Interdit de masquer une variable parente
			'no-console': [ 'warn', { allow: [ 'warn', 'error' ] } ],
			'no-unused-vars': [
				'warn',
				{
					argsIgnorePattern: '^_',
					varsIgnorePattern: '^_',
					caughtErrorsIgnorePattern: '^_',
				},
			],

			// =================================================================
			// 2. ADAPTATIONS WORDPRESS & ARCHITECTURE (Légitimes)
			// =================================================================
			'camelcase': 'off', // Autorise le snake_case issu de PHP / WP
			'no-alert': 'off', // Autorise alert() et confirm() dans l'admin WP
			'prettier/prettier': 'off', // Ne bloque pas le linter sur du formatage pur
			'import/no-unresolved': 'off', // Délégué à TypeScript / Bundler
			'import/no-extraneous-dependencies': 'off',

			// =================================================================
			// 3. ALLÈGEMENT JSDOC (Géré par TypeScript / Noms explicites)
			// =================================================================
			'jsdoc/require-param': 'off',
			'jsdoc/require-param-type': 'off',
			'jsdoc/require-returns': 'off',
			'jsdoc/require-returns-type': 'off',
			'jsdoc/check-param-names': 'off',
		},
	},
];