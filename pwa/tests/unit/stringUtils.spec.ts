import { describe, expect, test } from 'vitest';
import { removeAccents, includesNormalized, decodeHtmlEntities, getContenuTypeLabel } from '@/utils/stringUtils';

describe('stringUtils', () => {
  describe('removeAccents', () => {
    test('removes accents from French vowels and diacritics', () => {
      expect(removeAccents('Échiquier Lédonien')).toBe('Echiquier Ledonien');
      expect(removeAccents('évènement')).toBe('evenement');
      expect(removeAccents('àâäéèêëîïôöùûüç')).toBe('aaaeeeeiioouuuc');
      expect(removeAccents('ÀÂÄÉÈÊËÎÏÔÖÙÛÜÇ')).toBe('AAAEEEEIIOOUUUC');
    });

    test('returns unaccented text unchanged', () => {
      expect(removeAccents('chess')).toBe('chess');
      expect(removeAccents('12345')).toBe('12345');
    });

    test('handles empty, null, or undefined values gracefully', () => {
      expect(removeAccents('')).toBe('');
      expect(removeAccents(null)).toBe('');
      expect(removeAccents(undefined)).toBe('');
    });
  });

  describe('includesNormalized', () => {
    test('matches substring case-insensitively and accent-insensitively', () => {
      expect(includesNormalized('Échiquier Lédonien', 'ledonien')).toBe(true);
      expect(includesNormalized('Échiquier Lédonien', 'ECHIQUIER')).toBe(true);
      expect(includesNormalized('Prochains Anniversaires', 'anniversaire')).toBe(true);
    });

    test('returns false when query is not contained in target', () => {
      expect(includesNormalized('Échiquier Lédonien', 'tournoi')).toBe(false);
    });

    test('handles empty query or target correctly', () => {
      expect(includesNormalized('Échiquier', '')).toBe(true);
      expect(includesNormalized(null, 'test')).toBe(false);
      expect(includesNormalized(undefined, 'test')).toBe(false);
    });
  });

  describe('decodeHtmlEntities', () => {
    test('decodes numeric and named HTML entities correctly', () => {
      expect(decodeHtmlEntities('1 &#8211; Matérialité')).toBe('1 – Matérialité');
      expect(decodeHtmlEntities('T1 &#8211; 100 Commandements')).toBe('T1 – 100 Commandements');
      expect(decodeHtmlEntities('T8 &#8211; Vision&rsquo;checs')).toBe('T8 – Vision’checs');
      expect(decodeHtmlEntities('Échecs &amp; Mat')).toBe('Échecs & Mat');
    });

    test('returns plain text unchanged', () => {
      expect(decodeHtmlEntities('Simple Text')).toBe('Simple Text');
    });

    test('handles empty, null, or undefined values gracefully', () => {
      expect(decodeHtmlEntities('')).toBe('');
      expect(decodeHtmlEntities(null)).toBe('');
      expect(decodeHtmlEntities(undefined)).toBe('');
    });
  });

  describe('getContenuTypeLabel', () => {
    test('identifies lessons correctly', () => {
      expect(getContenuTypeLabel({ type: 'roi_lecon', titre: 'Leçon 1' })).toBe('Leçon');
    });

    test('extracts exercise type labels from titles', () => {
      expect(getContenuTypeLabel({ type: 'roi_exercice', titre: 'T1 &#8211; 100 Commandements' })).toBe('100 Commandements');
      expect(getContenuTypeLabel({ type: 'roi_exercice', titre: 'T8 &#8211; Vision&rsquo;checs' })).toBe("Vision'checs");
      expect(getContenuTypeLabel({ type: 'roi_exercice', titre: 'T14 &#8211; Cap ou pas Cap ?' })).toBe('Cap ou pas Cap ?');
      expect(getContenuTypeLabel({ type: 'roi_exercice', titre: 'T9 &#8211; Parcours' })).toBe('Parcours');
      expect(getContenuTypeLabel({ type: 'roi_exercice', titre: 'T16 &#8211; Destination finale' })).toBe('Destination finale');
    });

    test('uses exercice_type number when provided', () => {
      expect(getContenuTypeLabel({ type: 'roi_exercice', exercice_type: 14 })).toBe('Cap ou pas Cap ?');
      expect(getContenuTypeLabel({ type: 'roi_exercice', exercice_type: 1 })).toBe('100 Commandements');
    });

    test('falls back to Exercice for unknown exercise formats', () => {
      expect(getContenuTypeLabel({ type: 'roi_exercice', titre: 'Exercice Custom' })).toBe('Exercice');
      expect(getContenuTypeLabel({ type: 'roi_exercice' })).toBe('Exercice');
      expect(getContenuTypeLabel(null)).toBe('');
    });
  });
});
