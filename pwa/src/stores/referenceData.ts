import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';

export interface ReferenceItem {
  code: string;
  name: string;
}

export const useReferenceDataStore = defineStore('referenceData', () => {
  const getHeaders = () => {
    const token = localStorage.getItem('dame_jwt_token');
    const headers: Record<string, string> = {
      'Content-Type': 'application/json'
    };
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    return headers;
  };

  // 1. Regions Query
  const { data: rawRegions, isLoading: isRegionsLoading } = useQuery<ReferenceItem[]>({
    queryKey: ['reference', 'regions'],
    queryFn: async () => {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await fetch(`${apiUrl}/dame/v1/data/regions`, {
        headers: getHeaders()
      });
      if (!response.ok) throw new Error("Erreur fetchRegions");

      const data = await response.json();
      let formatted: ReferenceItem[] = [];

      if (data && typeof data === 'object' && !Array.isArray(data)) {
        formatted = Object.entries(data).map(([code, name]) => ({
          code,
          name: name as string
        }));
      } else if (Array.isArray(data)) {
        formatted = data.map((item: any) => ({
          code: item.code || item.id || item.slug || '',
          name: item.name || item.label || ''
        })).filter(item => item.code && item.name);
      }

      formatted.sort((a, b) => a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }));
      return formatted;
    }
  });

  const regions = computed(() => rawRegions.value || []);

  // 2. Departments Query
  const { data: rawDepartments, isLoading: isDepartmentsLoading } = useQuery<ReferenceItem[]>({
    queryKey: ['reference', 'departments'],
    queryFn: async () => {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await fetch(`${apiUrl}/dame/v1/data/departments`, {
        headers: getHeaders()
      });
      if (!response.ok) throw new Error("Erreur fetchDepartments");

      const data = await response.json();
      let formatted: ReferenceItem[] = [];

      if (data && typeof data === 'object' && !Array.isArray(data)) {
        formatted = Object.entries(data).map(([code, name]) => ({
          code,
          name: name as string
        }));
      } else if (Array.isArray(data)) {
        formatted = data.map((item: any) => ({
          code: item.code || item.id || item.slug || '',
          name: item.name || item.label || ''
        })).filter(item => item.code && item.name);
      }

      formatted.sort((a, b) => a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }));
      return formatted;
    }
  });

  const departments = computed(() => rawDepartments.value || []);

  // 3. Mapping Query
  const { data: rawDeptRegionMap } = useQuery<Record<string, string>>({
    queryKey: ['reference', 'mapping'],
    queryFn: async () => {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await fetch(`${apiUrl}/dame/v1/data/department-region-mapping`, {
        headers: getHeaders()
      });
      if (!response.ok) throw new Error("Erreur fetchMapping");
      return await response.json();
    }
  });

  const deptRegionMap = computed(() => rawDeptRegionMap.value || {});

  const isLoading = computed(() => isRegionsLoading.value || isDepartmentsLoading.value);

  const fetchRegions = async () => {};
  const fetchDepartments = async () => {};
  const fetchMapping = async () => {};

  return {
    regions,
    departments,
    deptRegionMap,
    isLoading,
    fetchRegions,
    fetchDepartments,
    fetchMapping
  };
});

