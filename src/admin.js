class AskellUI {
	static usersTab() {
		return document.querySelector( '#askell-nav-tab-users' );
	}

	static settingsTab() {
		return document.querySelector( '#askell-nav-tab-settings' );
	}

	static usersForm() {
		return document.querySelector( '#askell-registration-users' );
	}

	static settingsForm() {
		return document.querySelector( '#askell-registration-settings' );
	}
	static settingsLoader() {
		return document.querySelector( '#askell-settings-loader' );
	}
	static settingsSubmit() {
		return document.querySelector( '#askell-settings-loader input[type=submit]' );
	}

	static settingsTabClickEvent( event ) {
		event.preventDefault();
		AskellUI.usersForm().classList.add( 'hidden' );
		AskellUI.settingsForm().classList.remove( 'hidden' );
		AskellUI.usersTab().classList.remove( 'nav-tab-active' );
		AskellUI.settingsTab().classList.add( 'nav-tab-active' );
	}

	static usersTabClickEvent( event ) {
		event.preventDefault();
		AskellUI.usersForm().classList.remove( 'hidden' );
		AskellUI.settingsForm().classList.add( 'hidden' );
		AskellUI.usersTab().classList.add( 'nav-tab-active' );
		AskellUI.settingsTab().classList.remove( 'nav-tab-active' );
	}

	// This is what happens when the form is submitted
	static onSettingsFormSubmit( event ) {
		event.preventDefault();

		let formData = new FormData(event.target);
		let formDataObject = {
			api_key: formData.get('api_key'),
			api_secret: formData.get('api_secret'),
			reference: formData.get('reference'),
			enable_address_country: Boolean(
				formData.get('enable_address_country')
			),
			enable_css: Boolean(
				formData.get('enable_css')
			)
		}

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
					'X-WP-Nonce': wpApiSettings.nonce
				},
				body: JSON.stringify(formDataObject)
			}
		);

		const result = await response.json();

		AskellUI.settingsLoader().classList.add('hidden');
		AskellUI.settingsSubmit.disabled = false;
	}
}

window.addEventListener( 'DOMContentLoaded', () => {
	if ( document.body.classList.contains( 'toplevel_page_askell-registration' ) ) {
		AskellUI.settingsTab().addEventListener(
			'click',
			AskellUI.settingsTabClickEvent
		);

		AskellUI.usersTab().addEventListener(
			'click',
			AskellUI.usersTabClickEvent
		);

		AskellUI.settingsForm().addEventListener(
			'submit',
			AskellUI.onSettingsFormSubmit
		)
	}
} );

