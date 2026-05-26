import { sanitizeHtml } from '@/utils/sanitize';
import type { Directive } from 'vue';

/**
 * Directive Vue pour injecter du HTML sanitizé de manière sécurisée.
 * Usage: <div v-safe-html="rawHtml"></div>
 */
export const vSafeHtml: Directive = {
  mounted(el: HTMLElement, binding) {
    el.innerHTML = sanitizeHtml(binding.value);
  },
  updated(el: HTMLElement, binding) {
    if (binding.value === binding.oldValue) return;
    el.innerHTML = sanitizeHtml(binding.value);
  }
};
