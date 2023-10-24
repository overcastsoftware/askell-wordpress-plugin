import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useState, useEffect, useReducer } from "react";
import { CheckboxControl, RadioControl, __experimentalDivider as Divider } from '@wordpress/components';

const { __ } = wp.i18n;
const { PluginDocumentSettingPanel } = wp.editPost;
const { PanelRow } = wp.components;
const { compose } = wp.compose;
const { withSelect, withDispatch } = wp.data;

const AskellPostSidebarPanel = ( { postType, postMeta, setPostMeta } ) => {

	if ( false === ['post', 'page'].includes(postType) ) {
		return null;
	}

	const [plansLoaded, setPlansLoaded] = useState(false);
	const [plans, setPlans] = useState([]);

	useEffect(() => {
		if ( plansLoaded === false ) {
			apiFetch( { path: '/askell/v1/plans' } ).then( ( plans ) => {
				setPlans(plans);
				setPlansLoaded(true);
			} );
		}
	});

	function onChangeVisibility(value) {
		setPostMeta({ askell_visibility: value });
	}

	function onChangePlanCheckbox( id, checked ) {
		let currentIdsArray = [];
		if ( postMeta.askell_plan_ids.length !== 0 ) {
			currentIdsArray = postMeta.askell_plan_ids.split(',');
		}

		if ( true === checked ) {
			currentIdsArray.push(id);
			setPostMeta({askell_plan_ids: currentIdsArray.join(',')});
		}
		else {
			let index = currentIdsArray.indexOf(id);
			if (index > -1) {
				currentIdsArray.splice(index,1);
				setPostMeta({askell_plan_ids: currentIdsArray.join(',')});
			}
		}
	}

	function planCheckboxChecked( id ) {
		const idString = id.toString();
		return postMeta.askell_plan_ids.split(',').includes(idString);
	}

	return(
		<PluginDocumentSettingPanel
			title={ __( 'Askell', 'askell-registration') }
			initialOpen="true"
		>
			<PanelRow>
				<RadioControl
					label={ __('Post Availability', 'askell-registration') }
					options={ [
						{
							label: __('Publicly Available', 'askell-registration'),
							value: 'public'
						},
						{
							label: __('Subscribers Only', 'askell-registration'),
							value: 'subscribers'
						},
						{
							label: __('Subscribers with Specific Plans', 'askell-registration'),
							value: 'specific_plans'
						}
					] }
					selected={ postMeta.askell_visibility }
					onChange={ ( value ) => onChangeVisibility( value ) }
				/>
			</PanelRow>
			{ postMeta.askell_visibility == 'specific_plans' &&
			<div>
				<fieldset
					id="askell-post-panel-specific-plans-fieldset"
					aria-label="Plans"
				>
					{ plans.map( p => (
						<div className='askell-post-panel-plan-container'>
							<label
								className='askell-post-panel-plan-checkbox'
							>
								<input
									type='checkbox'
									data-plan-id={ p.id }
									checked={ planCheckboxChecked( p.id ) }
									onChange={ ( e ) => onChangePlanCheckbox( e.target.dataset.planId, e.target.checked ) }
								/>
								{p.name}
							</label>
						</div>
					)) }
				</fieldset>
			</div>
			}
		</PluginDocumentSettingPanel>
	);
}

export default compose( [
	withSelect( ( select ) => {
		return {
			postMeta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
			postType: select( 'core/editor' ).getCurrentPostType(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		return {
			setPostMeta( newMeta ) {
				dispatch( 'core/editor' ).editPost( { meta: newMeta } );
			}
		};
	} )
] )( AskellPostSidebarPanel );
