class AskellUI {
	static settingsForm() {
		return document.querySelector('#askell-registration-settings');
	}
	static settingsLoader() {
		return document.querySelector('#askell-settings-loader');
	}
	static settingsSubmit() {
		return document.querySelector(
			'#askell-settings-loader input[type=submit]'
		);
	}

	// This is what happens when the form is submitted
	static onSettingsFormSubmit(event) {
		event.preventDefault();

		const formData = new FormData(event.target);
		const formDataObject = {
			api_key: formData.get('api_key').trim(),
			api_secret: formData.get('api_secret').trim(),
			customer_webhook_secret: formData
				.get('customer_webhook_secret')
				.trim(),
			subscription_webhook_secret: formData
				.get('subscription_webhook_secret')
				.trim(),
			enable_css: Boolean(formData.get('enable_css')),
		};

		// Spin the loader and disable the submit button
		AskellUI.settingsLoader().classList.remove('hidden');
		AskellUI.settingsSubmit.disabled = true;

		// Hand the form data over to the async postSettingsData function
		AskellUI.postSettingsData(formDataObject);
	}

	// Posts updated settings to the settings JSON endpoint
	//
	// Note that you need to generate a WordPress nonce and use the X-WP-Nonce
	// header if you want to test the endpoint with something like Postman.
	static async postSettingsData(formDataObject) {
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/settings',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify(formDataObject),
			}
		);

		if (response.ok) {
			AskellUI.settingsLoader().classList.add('hidden');
			AskellUI.settingsSubmit.disabled = false;
		}
	}

	static profilePersonalInformationForm() {
		return document.querySelector(
			'#askell-profile-personal-information-form'
		);
	}

	static profilePersonalInformationLoader() {
		return document.querySelector('#askell-profile-user-info-loader');
	}

	static profilePersonalInformationSubmit() {
		return document.querySelector('#askell-profile-user-info-submit');
	}

	static profilePersonalInformationErrorDisplay() {
		return document.querySelector(
			'#askell-profile-personal-information-form-error-display'
		);
	}

	static onProfilePersonalInformationFormSubmit(event) {
		event.preventDefault();

		const formData = new FormData(event.target);
		const formDataObject = {
			first_name: formData.get('first_name').trim(),
			last_name: formData.get('last_name').trim(),
			email: formData.get('email').trim(),
		};

		console.log(formData);
		console.log(formDataObject);

		AskellUI.profilePersonalInformationLoader().classList.remove('hidden');
		AskellUI.profilePersonalInformationSubmit().disabled = true;

		AskellUI.postProfilePersonalInformation(formDataObject);
	}

	static async postProfilePersonalInformation(formDataObject) {
		const errorDisplay = AskellUI.profilePersonalInformationErrorDisplay();
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/my_user_info',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify(formDataObject),
			}
		);

		const responseData = await response.json();

		if (response.status) {
			AskellUI.profilePersonalInformationLoader().classList.add('hidden');
			AskellUI.profilePersonalInformationSubmit().disabled = false;
		}

		if (response.ok) {
			errorDisplay.innerText = '';
			window.location.reload();
		} else {
			errorDisplay.innerText = responseData.message;
		}
	}

	static profilePasswordForm() {
		return document.querySelector('#askell-profile-password-form');
	}

	static profilePasswordLoader() {
		return document.querySelector('#askell-profile-password-loader');
	}

	static profilePasswordSubmit() {
		return document.querySelector('#askell-profile-password-submit');
	}

	static profilePasswordErrorDisplay() {
		return document.querySelector(
			'#askell-profile-password-form-error-display'
		);
	}

	static onProfilePasswordFormSubmit(event) {
		event.preventDefault();

		const formData = new FormData(event.target);
		const formDataObject = {
			password: formData.get('password').trim(),
			password_confirm: formData.get('password_confirm').trim(),
		};

		AskellUI.profilePasswordLoader().classList.remove('hidden');
		AskellUI.profilePasswordSubmit().disabled = true;

		AskellUI.postProfilePassword(formDataObject);
	}

	static async postProfilePassword(formDataObject) {
		const errorDisplay = AskellUI.profilePasswordErrorDisplay();
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/my_password',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify(formDataObject),
			}
		);

		const responseData = await response.json();

		if (response.status) {
			AskellUI.profilePasswordLoader().classList.add('hidden');
			AskellUI.profilePasswordSubmit().disabled = false;
		}

		if (response.ok) {
			errorDisplay.innerText = '';
			window.location.reload();
		} else {
			errorDisplay.innerText = responseData.message;
		}
	}

	static profileDeleteAccountCheckbox() {
		return document.querySelector('#delete-account-confirm-checkbox');
	}

	static profileDeleteAccountButton() {
		return document.querySelector('#delete-account-button');
	}

	static onProfileDeleteAccountCheckboxToggle(event) {
		if (event.target.checked) {
			console.log('checked!');
			AskellUI.profileDeleteAccountButton().disabled = false;
		} else {
			console.log('unchecked!');
			AskellUI.profileDeleteAccountButton().disabled = true;
		}
	}

	static onProfileDeleteAccountButtonClick(event) {
		event.preventDefault();

		AskellUI.postProfileDelete();
	}

	static profileDangerZoneErrorDisplay() {
		return document.querySelector('#danger-zone-error-display');
	}

	static async postProfileDelete() {
		const errorDisplay = AskellUI.profileDangerZoneErrorDisplay();
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/my_account',
			{
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
			}
		);

		const responseData = await response.json();

		if (response.ok) {
			errorDisplay.innerText = '';
			window.location.reload();
		} else {
			errorDisplay.innerText = responseData.message;
		}
	}
}

window.addEventListener('DOMContentLoaded', () => {
	if (document.body) {
		if (
			document.body.classList.contains(
				'toplevel_page_askell-registration'
			)
		) {
			AskellUI.settingsForm().addEventListener(
				'submit',
				AskellUI.onSettingsFormSubmit
			);
		}

		if (
			document.body.classList.contains(
				'toplevel_page_askell-registration-my-profile'
			)
		) {
			AskellUI.profilePersonalInformationForm().addEventListener(
				'submit',
				AskellUI.onProfilePersonalInformationFormSubmit
			);

			AskellUI.profilePasswordForm().addEventListener(
				'submit',
				AskellUI.onProfilePasswordFormSubmit
			);

			AskellUI.profileDeleteAccountCheckbox().addEventListener(
				'change',
				AskellUI.onProfileDeleteAccountCheckboxToggle
			);

			AskellUI.profileDeleteAccountButton().addEventListener(
				'click',
				AskellUI.onProfileDeleteAccountButtonClick
			);
		}
	}
});
