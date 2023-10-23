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
			customer_webhook_secret: formData.get(
				'customer_webhook_secret'
			).trim(),
			subscription_webhook_secret: formData.get(
				'subscription_webhook_secret'
			).trim(),
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
}

window.addEventListener('DOMContentLoaded', () => {
	if (document.body.classList.contains('toplevel_page_askell-registration')) {
		AskellUI.settingsForm().addEventListener(
			'submit',
			AskellUI.onSettingsFormSubmit
		);
	}
});
