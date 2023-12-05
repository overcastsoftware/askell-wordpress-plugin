import './editor.scss';

import { useBlockProps } from '@wordpress/block-editor';

import AskellRegistration from '../src/askellRegistration.js';

export default function Edit() {
	return (
		<div {...useBlockProps()}>
			<div className="askell-registration-frontend-block-container">
				<AskellRegistration />
			</div>
		</div>
	);
}
