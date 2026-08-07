import { ref, reactive } from 'vue';

export interface PreInscriptionFormData {
  dame_birth_name: string;
  dame_last_name: string;
  dame_first_name: string;
  dame_sexe: string;
  dame_birth_date: string;
  dame_birth_city: string;
  dame_phone_number: string;
  dame_email: string;
  dame_profession: string;
  dame_address_1: string;
  dame_address_2: string;
  dame_postal_code: string;
  dame_city: string;
  dame_taille_vetements: string;
  dame_license_type: string;
  dame_legal_rep_1_first_name: string;
  dame_legal_rep_1_last_name: string;
  dame_legal_rep_1_email: string;
  dame_legal_rep_1_phone: string;
  dame_legal_rep_1_address_1: string;
  dame_legal_rep_1_address_2: string;
  dame_legal_rep_1_postal_code: string;
  dame_legal_rep_1_city: string;
  dame_legal_rep_1_profession: string;
  dame_legal_rep_1_date_naissance: string;
  dame_legal_rep_1_commune_naissance: string;
  dame_legal_rep_2_first_name: string;
  dame_legal_rep_2_last_name: string;
  dame_legal_rep_2_email: string;
  dame_legal_rep_2_phone: string;
  dame_legal_rep_2_address_1: string;
  dame_legal_rep_2_address_2: string;
  dame_legal_rep_2_postal_code: string;
  dame_legal_rep_2_city: string;
  dame_legal_rep_2_profession: string;
  dame_legal_rep_2_date_naissance: string;
  dame_legal_rep_2_commune_naissance: string;
  dame_health_questionnaire: string;
  dame_refuses_comms: boolean;
  dame_legal_rep_1_refuses_comms: boolean;
  dame_legal_rep_2_refuses_comms: boolean;
}

export function usePreInscriptionForm() {
  const clothingSizes = ['Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

  const form = reactive<PreInscriptionFormData>({
    dame_birth_name: '',
    dame_last_name: '',
    dame_first_name: '',
    dame_sexe: 'Masculin',
    dame_birth_date: '',
    dame_birth_city: '',
    dame_phone_number: '',
    dame_email: '',
    dame_profession: '',
    dame_address_1: '',
    dame_address_2: '',
    dame_postal_code: '',
    dame_city: '',
    dame_taille_vetements: 'Non renseigné',
    dame_license_type: 'A',
    dame_legal_rep_1_first_name: '',
    dame_legal_rep_1_last_name: '',
    dame_legal_rep_1_email: '',
    dame_legal_rep_1_phone: '',
    dame_legal_rep_1_address_1: '',
    dame_legal_rep_1_address_2: '',
    dame_legal_rep_1_postal_code: '',
    dame_legal_rep_1_city: '',
    dame_legal_rep_1_profession: '',
    dame_legal_rep_1_date_naissance: '',
    dame_legal_rep_1_commune_naissance: '',
    dame_legal_rep_2_first_name: '',
    dame_legal_rep_2_last_name: '',
    dame_legal_rep_2_email: '',
    dame_legal_rep_2_phone: '',
    dame_legal_rep_2_address_1: '',
    dame_legal_rep_2_address_2: '',
    dame_legal_rep_2_postal_code: '',
    dame_legal_rep_2_city: '',
    dame_legal_rep_2_profession: '',
    dame_legal_rep_2_date_naissance: '',
    dame_legal_rep_2_commune_naissance: '',
    dame_health_questionnaire: '',
    dame_refuses_comms: false,
    dame_legal_rep_1_refuses_comms: false,
    dame_legal_rep_2_refuses_comms: false,
  });

  const consentCheckbox = ref(false);
  const isMinor = ref(false);

  const checkAge = () => {
    if (!form.dame_birth_date) {
      isMinor.value = false;
      return;
    }
    const birth = new Date(form.dame_birth_date);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    isMinor.value = age < 18;
  };

  const copyAdherentData = (repNum: number) => {
    const lastName = form.dame_last_name || form.dame_birth_name;
    if (repNum === 1) {
      form.dame_legal_rep_1_last_name = lastName;
      form.dame_legal_rep_1_first_name = form.dame_first_name;
      form.dame_legal_rep_1_phone = form.dame_phone_number;
      form.dame_legal_rep_1_email = form.dame_email;
      form.dame_legal_rep_1_address_1 = form.dame_address_1;
      form.dame_legal_rep_1_address_2 = form.dame_address_2;
      form.dame_legal_rep_1_postal_code = form.dame_postal_code;
      form.dame_legal_rep_1_city = form.dame_city;
    } else {
      form.dame_legal_rep_2_last_name = lastName;
      form.dame_legal_rep_2_first_name = form.dame_first_name;
      form.dame_legal_rep_2_phone = form.dame_phone_number;
      form.dame_legal_rep_2_email = form.dame_email;
      form.dame_legal_rep_2_address_1 = form.dame_address_1;
      form.dame_legal_rep_2_address_2 = form.dame_address_2;
      form.dame_legal_rep_2_postal_code = form.dame_postal_code;
      form.dame_legal_rep_2_city = form.dame_city;
    }
  };

  const resetForm = () => {
    form.dame_birth_name = '';
    form.dame_last_name = '';
    form.dame_first_name = '';
    form.dame_sexe = 'Masculin';
    form.dame_birth_date = '';
    form.dame_birth_city = '';
    form.dame_phone_number = '';
    form.dame_email = '';
    form.dame_profession = '';
    form.dame_address_1 = '';
    form.dame_address_2 = '';
    form.dame_postal_code = '';
    form.dame_city = '';
    form.dame_taille_vetements = 'Non renseigné';
    form.dame_license_type = 'A';
    form.dame_legal_rep_1_first_name = '';
    form.dame_legal_rep_1_last_name = '';
    form.dame_legal_rep_1_email = '';
    form.dame_legal_rep_1_phone = '';
    form.dame_legal_rep_1_address_1 = '';
    form.dame_legal_rep_1_address_2 = '';
    form.dame_legal_rep_1_postal_code = '';
    form.dame_legal_rep_1_city = '';
    form.dame_legal_rep_1_profession = '';
    form.dame_legal_rep_1_date_naissance = '';
    form.dame_legal_rep_1_commune_naissance = '';
    form.dame_legal_rep_2_first_name = '';
    form.dame_legal_rep_2_last_name = '';
    form.dame_legal_rep_2_email = '';
    form.dame_legal_rep_2_phone = '';
    form.dame_legal_rep_2_address_1 = '';
    form.dame_legal_rep_2_address_2 = '';
    form.dame_legal_rep_2_postal_code = '';
    form.dame_legal_rep_2_city = '';
    form.dame_legal_rep_2_profession = '';
    form.dame_legal_rep_2_date_naissance = '';
    form.dame_legal_rep_2_commune_naissance = '';
    form.dame_health_questionnaire = '';
    form.dame_refuses_comms = false;
    form.dame_legal_rep_1_refuses_comms = false;
    form.dame_legal_rep_2_refuses_comms = false;
    consentCheckbox.value = false;
    isMinor.value = false;
  };

  return {
    form,
    clothingSizes,
    consentCheckbox,
    isMinor,
    checkAge,
    copyAdherentData,
    resetForm,
  };
}
