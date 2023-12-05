import { createRoot } from 'react-dom/client';
import { createElement } from 'react';

// Import our React component
import AskellRegistration from '../src/askellRegistration.js';

addEventListener('DOMContentLoaded', () => {
	// Find matching WordPress blocks
	const containers = document.querySelectorAll(
		'.askell-registration-frontend-block-container'
	);

	// Assign a root and create component in every DOM element
	// matching the query selector.
	containers.forEach(function (container) {
		const askellRegistrationRoot = createRoot(container);
		askellRegistrationRoot.render(createElement(AskellRegistration, null));
	});
});
