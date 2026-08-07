import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import type { PreInscriptionFormData } from './usePreInscriptionForm';

export interface RegistrationTarget {
  member_id: number;
  name: string;
  relation: string;
}

export function usePreInscriptionApi() {
  const authStore = useAuthStore();

  const registrationTargets = ref<RegistrationTarget[]>([]);
  const selectedTargetId = ref<number>(0);
  const completedMemberIds = ref<number[]>([]);
  const hasLoadedIdentities = ref(false);

  const isSubmitting = ref(false);
  const errorMessage = ref('');
  const successData = ref<any>(null);

  /**
   * Charge les identités associées au compte utilisateur
   */
  const loadIdentities = async (prefillCallback: (memberId: number) => void) => {
    if (!authStore.isAuthenticated) return;

    try {
      const identities = await authStore.fetchMyIdentities();
      const targets: RegistrationTarget[] = [];

      identities.forEach((identity: any) => {
        if (identity.type === 'member' && identity.member_id > 0 && !identity.already_registered) {
          targets.push({
            member_id: identity.member_id,
            name: identity.name,
            relation: 'Moi-même',
          });
        }
        if (identity.type === 'representative' && identity.associated_members) {
          identity.associated_members.forEach((child: any) => {
            if (!child.already_registered && !targets.some((t) => t.member_id === child.member_id)) {
              targets.push({
                member_id: child.member_id,
                name: child.firstname || child.name,
                relation: 'Enfant/Associé',
              });
            }
          });
        }
      });

      registrationTargets.value = targets;
      hasLoadedIdentities.value = true;

      if (targets.length === 1) {
        selectedTargetId.value = targets[0].member_id;
        prefillCallback(targets[0].member_id);
      } else if (
        targets.length > 1 &&
        authStore.selectedIdentity &&
        authStore.selectedIdentity.member_id > 0 &&
        !authStore.selectedIdentity.already_registered
      ) {
        selectedTargetId.value = authStore.selectedIdentity.member_id;
        prefillCallback(authStore.selectedIdentity.member_id);
      }
    } catch (err) {
      console.error('Erreur lors du chargement des identités:', err);
    }
  };

  /**
   * Pré-remplit les données d'un membre
   */
  const prefillAdherent = async (memberId: number, form: PreInscriptionFormData, checkAge: () => void) => {
    errorMessage.value = '';

    const makeRequest = async () => {
      return fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/adherent-details?adherent_id=${memberId}`, {
        headers: { Authorization: `Bearer ${authStore.token}` },
      });
    };

    try {
      let response = await makeRequest();

      if (response.status === 401) {
        console.warn('Token expiré ou invalide (401), tentative de rafraîchissement...');
        await authStore.validateSession();
        response = await makeRequest();
      }

      if (response.ok) {
        const data = await response.json();
        Object.keys(data).forEach((key) => {
          const formKey = `dame_${key}` as keyof PreInscriptionFormData;
          if (formKey in form && data[key] !== null && data[key] !== undefined) {
            (form as any)[formKey] = data[key];
          }
        });
        checkAge();
      } else if (response.status === 401) {
        console.error('Session définitivement expirée.');
        authStore.logout();
      }
    } catch (err) {
      console.error('Erreur lors de la récupération des détails adhérent:', err);
    }
  };

  /**
   * Soumet le formulaire de pré-inscription
   */
  const submitForm = async (form: PreInscriptionFormData, consentCheckbox: boolean) => {
    errorMessage.value = '';
    isSubmitting.value = true;

    try {
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authStore.isAuthenticated) {
        headers['Authorization'] = `Bearer ${authStore.token}`;
      }

      const bodyData = {
        ...form,
        dame_consent_checkbox: consentCheckbox ? '1' : '',
      };

      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/pre-inscription`, {
        method: 'POST',
        headers,
        body: JSON.stringify(bodyData),
      });

      const resData = await response.json();

      if (!response.ok) {
        errorMessage.value = resData.message || 'Une erreur est survenue lors de la validation.';
        return;
      }

      successData.value = resData;

      if (selectedTargetId.value > 0) {
        completedMemberIds.value.push(selectedTargetId.value);
      }
    } catch (err) {
      console.error(err);
      errorMessage.value = 'Erreur de connexion au serveur.';
    } finally {
      isSubmitting.value = false;
    }
  };

  /**
   * Téléchargement de document PDF (Santé / Autorisation Parentale)
   */
  const downloadPdf = async (type: 'health' | 'parental', form: PreInscriptionFormData) => {
    if (!successData.value) return;
    const post_id = successData.value.post_id;
    const token = successData.value.download_token;

    try {
      const url = `${import.meta.env.VITE_API_BASE_URL}/dame/v1/pre-inscriptions/${post_id}/pdf/${type}?token=${token}`;
      const headers: Record<string, string> = {};
      if (authStore.isAuthenticated) {
        headers['Authorization'] = `Bearer ${authStore.token}`;
      }

      const response = await fetch(url, { headers });
      if (!response.ok) throw new Error('Erreur de téléchargement');

      const blob = await response.blob();
      const blobUrl = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = blobUrl;

      const lastName = (form.dame_last_name || '').trim().replace(/\s+/g, '_').toUpperCase();
      const firstName = (form.dame_first_name || '').trim().replace(/\s+/g, '_');
      const nameSuffix = lastName && firstName ? `_${lastName}_${firstName}` : '';
      const baseName = type === 'health' ? 'attestation_sante' : 'autorisation_parentale';

      link.download = `${baseName}${nameSuffix}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(blobUrl);
    } catch (err) {
      console.error(err);
      alert('Impossible de générer et télécharger le document PDF.');
    }
  };

  return {
    registrationTargets,
    selectedTargetId,
    completedMemberIds,
    hasLoadedIdentities,
    isSubmitting,
    errorMessage,
    successData,
    loadIdentities,
    prefillAdherent,
    submitForm,
    downloadPdf,
  };
}
