import { describe, expect, test } from 'vitest';
import { removeAccents, includesNormalized } from '@/utils/stringUtils';

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
});
