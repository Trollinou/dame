document.addEventListener('DOMContentLoaded', (): void => {
	const sendBtn = document.getElementById(
		'dame_send_test_btn'
	) as HTMLButtonElement | null;
	if (!sendBtn) {
		return;
	}

	sendBtn.addEventListener('click', (): void => {
		const emailInput = document.getElementById(
			'dame_test_email'
		) as HTMLInputElement | null;
		const email = emailInput ? emailInput.value : '';
		const postId = dame_test_send_data.post_id;
		const nonce = dame_test_send_data.nonce;

		if (!email) {
			alert(dame_test_send_data.alert_empty);
			return;
		}

		const spinner = document.getElementById('dame_test_spinner');
		if (spinner) {
			spinner.classList.add('is-active');
		}

		const resultEl = document.getElementById('dame_test_result');
		if (resultEl) {
			resultEl.innerHTML = '';
		}

		const form = document.createElement('form');
		form.action = dame_test_send_data.admin_url;
		form.method = 'post';

		const fields: Record<string, string> = {
			action: 'dame_send_test_email',
			post_ID: String(postId),
			test_email: email,
			_wpnonce: nonce,
		};

		for (const [key, value] of Object.entries(fields)) {
			const hidden = document.createElement('input');
			hidden.type = 'hidden';
			hidden.name = key;
			hidden.value = value;
			form.appendChild(hidden);
		}

		document.body.appendChild(form);
		form.submit();
	});
});
