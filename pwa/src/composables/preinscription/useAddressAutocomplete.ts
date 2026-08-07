import { reactive } from 'vue';
import { fetchCitySuggestions, fetchAddressSuggestions, type GeoAddressResult } from '@/utils/geoApi';
import type { PreInscriptionFormData } from './usePreInscriptionForm';

export function useAddressAutocomplete(form: PreInscriptionFormData) {
  const suggestions = reactive({
    birthCity: [] as string[],
    rep1BirthCity: [] as string[],
    rep2BirthCity: [] as string[],
    address: [] as GeoAddressResult[],
    rep1Address: [] as GeoAddressResult[],
    rep2Address: [] as GeoAddressResult[],
  });

  let searchTimeout: ReturnType<typeof setTimeout> | null = null;

  const searchBirthCity = (query: string, repNum = 0) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (query.trim().length < 3) {
      clearSuggestions(repNum);
      return;
    }
    searchTimeout = setTimeout(async () => {
      const results = await fetchCitySuggestions(query);
      if (repNum === 0) suggestions.birthCity = results;
      else if (repNum === 1) suggestions.rep1BirthCity = results;
      else if (repNum === 2) suggestions.rep2BirthCity = results;
    }, 250);
  };

  const selectBirthCity = (city: string, repNum = 0) => {
    if (repNum === 0) {
      form.dame_birth_city = city;
      suggestions.birthCity = [];
    } else if (repNum === 1) {
      form.dame_legal_rep_1_commune_naissance = city;
      suggestions.rep1BirthCity = [];
    } else if (repNum === 2) {
      form.dame_legal_rep_2_commune_naissance = city;
      suggestions.rep2BirthCity = [];
    }
  };

  const searchAddress = (query: string, repNum = 0) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (query.trim().length < 5) {
      if (repNum === 0) suggestions.address = [];
      else if (repNum === 1) suggestions.rep1Address = [];
      else if (repNum === 2) suggestions.rep2Address = [];
      return;
    }
    searchTimeout = setTimeout(async () => {
      const results = await fetchAddressSuggestions(query);
      if (repNum === 0) suggestions.address = results;
      else if (repNum === 1) suggestions.rep1Address = results;
      else if (repNum === 2) suggestions.rep2Address = results;
    }, 250);
  };

  const selectAddress = (feature: GeoAddressResult, repNum = 0) => {
    const text = feature.fulltext.split(',')[0].trim();
    if (repNum === 0) {
      form.dame_address_1 = text;
      form.dame_postal_code = feature.zipcode;
      form.dame_city = feature.city;
      suggestions.address = [];
    } else if (repNum === 1) {
      form.dame_legal_rep_1_address_1 = text;
      form.dame_legal_rep_1_postal_code = feature.zipcode;
      form.dame_legal_rep_1_city = feature.city;
      suggestions.rep1Address = [];
    } else if (repNum === 2) {
      form.dame_legal_rep_2_address_1 = text;
      form.dame_legal_rep_2_postal_code = feature.zipcode;
      form.dame_legal_rep_2_postal_code = feature.zipcode;
      form.dame_legal_rep_2_city = feature.city;
      suggestions.rep2Address = [];
    }
  };

  const clearSuggestions = (repNum: number) => {
    if (repNum === 0) suggestions.birthCity = [];
    else if (repNum === 1) suggestions.rep1BirthCity = [];
    else if (repNum === 2) suggestions.rep2BirthCity = [];
  };

  return {
    suggestions,
    searchBirthCity,
    selectBirthCity,
    searchAddress,
    selectAddress,
    clearSuggestions,
  };
}
