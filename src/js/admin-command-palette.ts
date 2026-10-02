/**
 * WordPress Admin Command Palette Integration for DAME.
 *
 * Registers fast-action navigation shortcuts in the WordPress Command Palette (Ctrl+K / Cmd+K).
 */

interface CommandConfig {
	name: string;
	label: string;
	icon?: string;
	callback: () => void;
}

interface WPCommandsAPI {
	registerCommand: (config: CommandConfig) => void;
}

function initDAMECommands(): void {
	const wpObj = window.wp as { commands?: WPCommandsAPI } | undefined;
	if (!wpObj?.commands?.registerCommand) {
		return;
	}

	const register = wpObj.commands.registerCommand;
	const getUrl = (path: string): string => {
		const base = window.dameAdminCommands?.adminUrl || 'admin.php';
		return base.endsWith('/')
			? `${base}${path}`
			: `${base.replace(/[^/]+$/, '')}${path}`;
	};

	const commands: CommandConfig[] = [
		{
			name: 'dame:new-adherent',
			label: 'DAME : Ajouter un nouvel adhérent',
			icon: 'groups',
			callback: () => {
				window.location.href = getUrl(
					'post-new.php?post_type=adherent'
				);
			},
		},
		{
			name: 'dame:new-event',
			label: 'DAME : Créer un événement / tournoi',
			icon: 'calendar-alt',
			callback: () => {
				window.location.href = getUrl(
					'post-new.php?post_type=dame_agenda'
				);
			},
		},
		{
			name: 'dame:new-benevolat',
			label: 'DAME : Créer un appel à bénévolat',
			icon: 'chart-bar',
			callback: () => {
				window.location.href = getUrl(
					'post-new.php?post_type=benevolat'
				);
			},
		},
		{
			name: 'dame:mailing',
			label: 'DAME : Envoyer un mailing',
			icon: 'email-alt',
			callback: () => {
				window.location.href = getUrl('admin.php?page=dame_mailing');
			},
		},
		{
			name: 'dame:ffe-sync',
			label: 'DAME : Synchroniser avec la FFE',
			icon: 'update',
			callback: () => {
				window.location.href = getUrl('admin.php?page=dame_import_ffe');
			},
		},
		{
			name: 'dame:settings',
			label: 'DAME : Ouvrir les réglages',
			icon: 'admin-generic',
			callback: () => {
				window.location.href = getUrl('admin.php?page=dame_settings');
			},
		},
	];

	commands.forEach((cmd) => {
		try {
			register(cmd);
		} catch {
			// Silently handle if command is already registered.
		}
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initDAMECommands);
} else {
	initDAMECommands();
}

export {};
