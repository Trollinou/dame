/**
 * Interactivity API Module - DAME Agenda Store.
 *
 * Provides reactive state management for the public agenda component
 * using WordPress 7.x Interactivity API and Signals.
 */

import { store, getContext } from '@wordpress/interactivity';
import type { AgendaEvent } from '../../types/api/agenda';

export interface AgendaContext {
	currentYear: number;
	currentMonth: number;
	monthTitle: string;
	startOfWeek: number;
	searchTerm: string;
	selectedCategories: string[];
	isFilterOpen: boolean;
	isLoading: boolean;
	activeEvent: AgendaEvent | null;
	isModalOpen: boolean;
	ajaxUrl: string;
	nonce: string;
}

const MONTH_NAMES = [
	'Janvier',
	'Février',
	'Mars',
	'Avril',
	'Mai',
	'Juin',
	'Juillet',
	'Août',
	'Septembre',
	'Octobre',
	'Novembre',
	'Décembre',
];

export const agendaStore = store('dame/agenda', {
	state: {
		get isFilterActive(): boolean {
			const ctx = getContext<AgendaContext>();
			return (
				ctx.selectedCategories.length > 0 ||
				ctx.searchTerm.trim().length > 0
			);
		},
		get currentMonthDisplay(): string {
			const ctx = getContext<AgendaContext>();
			return `${MONTH_NAMES[ctx.currentMonth]} ${ctx.currentYear}`;
		},
	},
	actions: {
		prevMonth(): void {
			const ctx = getContext<AgendaContext>();
			if (ctx.currentMonth === 0) {
				ctx.currentMonth = 11;
				ctx.currentYear -= 1;
			} else {
				ctx.currentMonth -= 1;
			}
			ctx.monthTitle = `${MONTH_NAMES[ctx.currentMonth]} ${ctx.currentYear}`;
			agendaStore.actions.fetchEvents();
		},

		nextMonth(): void {
			const ctx = getContext<AgendaContext>();
			if (ctx.currentMonth === 11) {
				ctx.currentMonth = 0;
				ctx.currentYear += 1;
			} else {
				ctx.currentMonth += 1;
			}
			ctx.monthTitle = `${MONTH_NAMES[ctx.currentMonth]} ${ctx.currentYear}`;
			agendaStore.actions.fetchEvents();
		},

		today(): void {
			const ctx = getContext<AgendaContext>();
			const now = new Date();
			ctx.currentYear = now.getFullYear();
			ctx.currentMonth = now.getMonth();
			ctx.monthTitle = `${MONTH_NAMES[ctx.currentMonth]} ${ctx.currentYear}`;
			agendaStore.actions.fetchEvents();
		},

		toggleFilters(): void {
			const ctx = getContext<AgendaContext>();
			ctx.isFilterOpen = !ctx.isFilterOpen;
		},

		onCategoryToggle(event: Event): void {
			const target = event.target as HTMLInputElement;
			if (!target) {
				return;
			}

			const ctx = getContext<AgendaContext>();
			const val = target.value;
			if (target.checked) {
				if (!ctx.selectedCategories.includes(val)) {
					ctx.selectedCategories.push(val);
				}
			} else {
				ctx.selectedCategories = ctx.selectedCategories.filter(
					(c: string) => c !== val
				);
			}
			agendaStore.actions.fetchEvents();
		},

		onSearchInput(event: Event): void {
			const ctx = getContext<AgendaContext>();
			const target = event.target as HTMLInputElement;
			if (target) {
				ctx.searchTerm = target.value;
				agendaStore.actions.fetchEvents();
			}
		},

		openEventModal(eventData: AgendaEvent): void {
			const ctx = getContext<AgendaContext>();
			ctx.activeEvent = eventData;
			ctx.isModalOpen = true;
		},

		closeModal(): void {
			const ctx = getContext<AgendaContext>();
			ctx.isModalOpen = false;
			ctx.activeEvent = null;
		},

		async fetchEvents(): Promise<void> {
			const ctx = getContext<AgendaContext>();
			ctx.isLoading = true;

			try {
				const startMonth = `${ctx.currentYear}-${String(ctx.currentMonth + 1).padStart(2, '0')}-01`;
				const endDay = new Date(
					ctx.currentYear,
					ctx.currentMonth + 1,
					0
				).getDate();
				const endMonth = `${ctx.currentYear}-${String(ctx.currentMonth + 1).padStart(2, '0')}-${String(endDay).padStart(2, '0')}`;

				const params = new URLSearchParams({
					action: 'dame_get_agenda_events',
					nonce: ctx.nonce,
					start: startMonth,
					end: endMonth,
				});

				if (ctx.selectedCategories.length > 0) {
					params.append(
						'categories',
						ctx.selectedCategories.join(',')
					);
				}

				if (ctx.searchTerm) {
					params.append('search', ctx.searchTerm);
				}

				const res = await fetch(`${ctx.ajaxUrl}?${params.toString()}`);
				if (res.ok) {
					const data = await res.json();
					if (data.success && Array.isArray(data.data)) {
						// Custom event dispatch or state update
						const event = new CustomEvent('dame:agenda:updated', {
							detail: data.data,
						});
						document.dispatchEvent(event);
					}
				}
			} catch (err) {
				console.error('[DAME Agenda] Error fetching events:', err);
			} finally {
				ctx.isLoading = false;
			}
		},
	},
	callbacks: {
		onInit(): void {
			const ctx = getContext<AgendaContext>();
			const urlParams = new URLSearchParams(window.location.search);
			const monthParam = urlParams.get('month');

			if (monthParam && /^\d{4}-\d{2}$/.test(monthParam)) {
				const [y, m] = monthParam.split('-').map(Number);
				ctx.currentYear = y;
				ctx.currentMonth = m - 1;
			} else {
				const now = new Date();
				ctx.currentYear = now.getFullYear();
				ctx.currentMonth = now.getMonth();
			}

			ctx.monthTitle = `${MONTH_NAMES[ctx.currentMonth]} ${ctx.currentYear}`;
		},
	},
});
