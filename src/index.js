/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType(metadata.name, {
	icon: {
		src: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 20 20"
				version="1.1"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path
					fill="black"
					d="m 10.97,1.1 -9.571,9.68 c -0.07,0.1 -0.07,0.17 0,0.24 l 1.395,1.41 c 0.07,0.1 0.173,0.1 0.241,0 0,0 0,0 0,0 L 11.09,4.284 c 0.1,-0.07 0.18,-0.07 0.24,0 0,0 0,0 0,0 l 1.42,1.436 v 0 h -1.13 c -0.1,0 -0.17,0.08 -0.17,0.17 v 1.421 c 0,0.09 0.1,0.17 0.17,0.17 h 4.21 c 0.19,0 0.34,-0.15 0.34,-0.34 V 2.873 c 0,-0.09 -0.1,-0.17 -0.17,-0.17 h -1.4 c -0.1,0 -0.17,0.08 -0.17,0.17 v 1.238 0 L 11.45,1.1 c -0.13,-0.1332 -0.34,-0.1347 -0.48,0 0,10e-4 0,0 0,0 z"
				/>
				<path
					fill="black"
					d="M 9.445,15.75 6.429,12.72 c -0.07,-0.1 -0.175,-0.1 -0.24,0 0,0 0,0 0,0 l -1.385,1.39 c -0.07,0.1 -0.07,0.18 0,0.24 l 4.521,4.55 c 0.13,0.13 0.345,0.13 0.48,0 0,0 0,0 0,0 L 18.6,10.06 c 0.1,-0.07 0.1,-0.179 0,-0.244 L 17.22,8.421 c -0.1,-0.07 -0.18,-0.07 -0.24,0 0,0 0,0 0,0 L 9.685,15.75 c -0.07,0.1 -0.175,0.1 -0.24,0 z"
				/>
			</svg>
		),
	},
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save,
});
