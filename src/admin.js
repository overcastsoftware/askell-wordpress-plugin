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
	}
} );

