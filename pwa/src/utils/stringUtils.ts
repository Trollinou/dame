/**
 * Utility functions for string manipulation and search filtering.
 */

/**
 * Strips accents and diacritics from a string using Unicode NFD decomposition.
 * Safe against null, undefined, or non-string inputs.
 *
 * @param str Input string
 * @returns String without accents
 */
export function removeAccents(str: string | null | undefined): string {
  if (!str) return '';
  return String(str).normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

/**
 * Checks if target string contains query string, case-insensitively and accent-insensitively.
 *
 * @param target Target string to search within
 * @param query Query string to search for
 * @returns True if target contains query, false otherwise
 */
export function includesNormalized(
  target: string | null | undefined,
  query: string | null | undefined
): boolean {
  if (!query) return true;
  if (!target) return false;

  const normalizedTarget = removeAccents(target).toLowerCase();
  const normalizedQuery = removeAccents(query).toLowerCase().trim();

  return normalizedTarget.includes(normalizedQuery);
}
