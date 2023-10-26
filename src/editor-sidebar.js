const { registerPlugin } = wp.plugins;

import AskellPostSidebarPanel from './askellPostSidebarPanel.js';

registerPlugin('askell-postmeta-plugin', {
	render() {
		return <AskellPostSidebarPanel />;
	},
});
