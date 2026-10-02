/**
 * Ambient type declarations for @wordpress/interactivity.
 */
declare module '@wordpress/interactivity' {
	export function store<
		T extends {
			state?: Record<string, unknown>;
			actions?: Record<string, (...args: any[]) => any>;
			callbacks?: Record<string, (...args: any[]) => any>;
			[key: string]: unknown;
		}
	>(
		namespace: string,
		storeDefinition: T
	): T;
	export function getContext<T = Record<string, unknown>>(): T;
	export function getElement(): { ref: HTMLElement; attributes: Record<string, unknown> };
}
