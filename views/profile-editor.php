<?php
/**
 * The subscriber admin view
 *
 * @package askell-registration
 */

$askell_registration = new AskellRegistration();
$user                = wp_get_current_user();
$subscriptions       = $user->askell_subscriptions;
?>

<div class="wrap">

	<h1>
		<?php esc_html_e( 'My Profile', 'askell-registration' ); ?>
	</h1>

	<form action="#" class="type-form" id="askell-profile-personal-information-form">
		<section class="section">
			<div class="setion-header">
				<h3><?php esc_html_e( 'Personal Information', 'askell-registration' ); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'First Name', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="first_name"
								value="<?php echo esc_attr( $user->first_name ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'Last Name', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="last_name"
								value="<?php echo esc_attr( $user->last_name ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'Email Address', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="email"
								name="email"
								value="<?php echo esc_attr( $user->user_email ); ?>"
							/>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th
							id="askell-profile-personal-information-form-error-display"
							class="error-display"
							colspan="2"
						>
						</th>
					</tr>
				</tfoot>
			</table>
			<p class="submit">
				<img
					id="askell-profile-user-info-loader"
					class="hidden"
					src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
					width="24"
					height="24"
				/>
				<input
					id="askell-profile-user-info-submit"
					type="submit"
					value="<?php esc_attr_e( 'Save', 'askell-registration' ); ?>"
					class="button button-primary"
				/>
			</p>
		</section>
	</form>
	<form action="#" class="type-form" id="askell-profile-password-form">
		<section class="section">
			<div class="setion-header">
				<h3><?php esc_html_e( 'Login information', 'askell-registration' ); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'Username', 'askell-registration' ); ?>
						</th>
						<td><?php echo esc_html( $user->user_login ); ?></td>
					</tr>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'Password', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="password"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'Password (repeat)', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="password"
								name="password_confirm"
							/>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th
							id="askell-profile-password-form-error-display"
							class="error-display"
							colspan="2"
						>
						</th>
					</tr>
				</tfoot>
			</table>
			<p class="submit">
				<img
					id="askell-profile-password-loader"
					class="hidden"
					src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
					width="24"
					height="24"
				/>
				<input
					id="askell-profile-password-submit"
					class="button button-primary"
					type="submit"
					value="<?php esc_attr_e( 'Update Password', 'askell-registration' ); ?>"
				/>
			</p>
		</section>
	</form>
	<div class="type-form">
		<section class="section">
			<div class="setion-header">
				<h3>
					<?php
					echo esc_html(
						_n(
							'Subscription',
							'Subscriptions',
							count( $subscriptions ),
							'askell-registration'
						)
					);
					?>
				</h3>
				<hr />
				<div class="subscriptions-subsection">
					<ul class="subscription-list">
						<?php
						foreach ( $subscriptions as $subscription ) :
							$plan = $askell_registration->get_plan_by_id( $subscription['plan_id'] );
							?>
						<li class="subscription-info">
							<strong class="plan-name"><?php echo esc_html( $plan['name'] ); ?></strong>
							<?php if ( true === $subscription['active'] ) : ?>
							<span class="pill pill-green"><?php echo esc_html_e( 'Active', 'askell-registration' ); ?></span>
							<?php else : ?>
							<span class="pill pill-red"><?php echo esc_html_e( 'Inactive', 'askell-registration' ); ?></span>
							<?php endif ?>
							<?php if ( true === $subscription['is_on_trial'] ) : ?>
							<span class="pill pill-grey"><?php echo esc_html_e( 'Trial', 'askell-registration' ); ?></span>
							<?php endif ?>
							<ul>
								<li class="description"><?php echo esc_html( $plan['description'] ); ?></li>
								<li class="price-tag"><?php echo esc_html( $plan['price_tag'] ); ?></li>
								<li class="trial-ends">
									<strong><?php echo esc_html_e( 'Trial Ends:', 'askell-registration' ); ?></strong>
									<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription['trial_end'] ) ) ); ?>
								</li>
							</ul>
						</li>
						<div class="subscription-list-button-container">
							<a
								href="https://askell.is/change_subscription/<?php echo esc_attr( $subscription['token'] ); ?>/"
								target="_blank"
								class="button button-primary"
							>
								<?php esc_html_e( 'Edit', 'askell-subscription' ); ?>
							</a>
						</div>
						<?php endforeach ?>
					</ul>
				</div>
				<p>
					<?php
					esc_html_e(
						'By clicking ‘Edit’ above, you will be taken to Askell, which is a secure external service used for managing your subscription and payment options on this website.',
						'askell-subscription'
					);
					?>
				</p>
			</div>
		</section>
		<section class="section danger-zone-section">
			<div class="setion-header">
				<h3>
					<?php esc_html_e( 'Danger Zone', 'askell-registration' ); ?>
				</h3>
				<hr />
			</div>
			<div class="danger-zone-subsection">
				<div class="danger-zone-subsection-description">
					<h4><?php echo esc_html_e( 'Delete Account', 'askell-registration' ); ?></h4>
					<p>
						<?php
						echo esc_html_e(
							'Deletes your account from this site. This also deletes your payment information from the subscription system.',
							'askell-registration'
						);
						?>
					</p>
				</div>
				<div class="danger-zone-button-container">
					<label>
						<input type="checkbox" id="delete-account-confirm-checkbox">
						<?php echo esc_html_e( 'Confirm deletion', 'askell-registration' ); ?>
					</label>
					<button
						id="delete-account-button"
						class="button"
						disabled
					>
						<?php echo esc_html_e( 'Delete My Account', 'askell-registration' ); ?>
					</button>
				</div>
			</div>
			<p id="danger-zone-error-display" class="error-display"></p>
		</section>
	</div>
</div>
