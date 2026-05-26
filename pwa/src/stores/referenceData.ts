import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface ReferenceItem {
  code: string;
  name: string;
}

export const useReferenceDataStore = defineStore('referenceData', () => {
  const regions = ref<ReferenceItem[]>([]);
  const departments = ref<ReferenceItem[]>([]);
  const deptRegionMap = ref<Record<string, string>>({});
  const isLoading = ref(false);

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

  const fetchRegions = async () => {
    if (regions.value.length > 0) return;
    isLoading.value = true;
    try {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await fetch(`${apiUrl}/dame/v1/data/regions`, {
        headers: getHeaders()
      });
      if (response.ok) {
        const data = await response.json();
        
        // Format spécifique : objet {"code": "Nom"}
        if (data && typeof data === 'object' && !Array.isArray(data)) {
          regions.value = Object.entries(data).map(([code, name]) => ({ 
            code, 
            name: name as string 
          }));
        } else if (Array.isArray(data)) {
          regions.value = data.map((item: any) => ({
            code: item.code || item.id || item.slug || '',
            name: item.name || item.label || ''
          })).filter(item => item.code && item.name);
        }
        
        // Tri par nom
        regions.value.sort((a, b) => a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }));
      }
    } catch (error) {
      console.error("Erreur fetchRegions:", error);
    } finally {
      isLoading.value = false;
    }
  };

  const fetchDepartments = async () => {
    if (departments.value.length > 0) return;
    isLoading.value = true;
    try {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await fetch(`${apiUrl}/dame/v1/data/departments`, {
        headers: getHeaders()
      });
      if (response.ok) {
        const data = await response.json();
        
        // Format spécifique : objet {"code": "Nom"}
        if (data && typeof data === 'object' && !Array.isArray(data)) {
          departments.value = Object.entries(data).map(([code, name]) => ({ 
            code, 
            name: name as string 
          }));
        } else if (Array.isArray(data)) {
          departments.value = data.map((item: any) => ({
            code: item.code || item.id || item.slug || '',
            name: item.name || item.label || ''
          })).filter(item => item.code && item.name);
        }
        
        // Tri par nom
        departments.value.sort((a, b) => a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }));
      }
    } catch (error) {
      console.error("Erreur fetchDepartments:", error);
    } finally {
      isLoading.value = false;
    }
  };

  const fetchMapping = async () => {
    if (Object.keys(deptRegionMap.value).length > 0) return;
    try {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await fetch(`${apiUrl}/dame/v1/data/department-region-mapping`, {
        headers: getHeaders()
      });
      if (response.ok) {
        deptRegionMap.value = await response.json();
      }
    } catch (error) {
      console.error("Erreur fetchMapping:", error);
    }
  };

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
