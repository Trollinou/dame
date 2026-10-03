document.addEventListener('DOMContentLoaded', (): void => {
	// --- Usage Name Fallback Logic (Specific to Adherent CPT) ---
	// This logic copies the birth name to the usage name if the usage name is empty.
	const birthNameInput = document.getElementById(
		'dame_birth_name'
	) as HTMLInputElement | null;
	const lastNameInput = document.getElementById(
		'dame_last_name'
	) as HTMLInputElement | null;

	if (birthNameInput && lastNameInput) {
		birthNameInput.addEventListener('blur', (): void => {
			if (birthNameInput.value && !lastNameInput.value) {
				lastNameInput.value = birthNameInput.value;
			}
		});
	}
});
