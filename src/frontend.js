// Import our React component
import AskellRegistration from '../src/askellRegistration.js';

// Find matching WordPress blocks
const containers = document.querySelectorAll(
	'.askell-registration-frontend-block-container'
);

// Assign a root and create component in every DOM element
// matching the query selector.
containers.forEach(function (container) {
	const askellRegistrationRoot = ReactDOM.createRoot(container);
	askellRegistrationRoot.render(
		React.createElement(AskellRegistration, null)
	);
});
