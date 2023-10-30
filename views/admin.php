<?php
/**
 * The subscriber admin view
 *
 * @package askell-registration
 */

global $askell;

?>

<div class="wrap">
	<div class="notice notice-warning">
		<p>
			<?php
			esc_html_e(
				'This is an early development version of Askell for WordPress. Do not use this version of the plugin on a production website!',
				'askell-registration'
			);
			?>
		</p>
	</div>

	<?php if ( empty( get_option( 'askell_api_key', '' ) ) || empty( get_option( 'askell_api_secret', '' ) ) ) : ?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'The Askell API key and Shared Secret values have not been set yet. Please enter the missing values.',
				'askell-registration'
			);
			?>
		</p>
	</div>
	<?php endif ?>

	<?php if ( empty( get_option( 'askell_customer_webhook_secret', '' ) ) || empty( get_option( 'askell_subscription_webhook_secret', '' ) ) ) : ?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'The Askell webhook HMAC secrets have not been set yet. Please enter the missing values.',
				'askell-registration'
			);
			?>
		</p>
	</div>
	<?php endif ?>

	<?php if ( 1 === get_option( 'users_can_register', false ) ) : ?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'Your General WordPress Settings have the option ‘anyone can register’ enabled. This enables external users to bypass the Askell registration block and can cause errors and inconsitencies between Askell and your WordPress site',
				'askell-registration'
			);
			?>
		</p>
	</div>
	<?php endif ?>

	<?php if ( true === empty( get_option( 'askell_register_url', '' ) ) ) : ?>
	<div class="notice notice-warning">
		<p>
			<?php
			esc_html_e(
				'The Askell Registration block has not been added to any page or post yet. Please add the block somewhere or set the URL manually below.',
				'askell-registration'
			);
			?>
		</p>
	</div>
	<?php endif ?>

	<h1>
		<?php esc_html_e( 'Askell for WordPress', 'askell-registration' ); ?>
	</h1>

	<form action="#" class="type-form" id="askell-registration-settings">
		<div>
			<h2><?php esc_html_e( 'Settings', 'askell-registration' ); ?></h2>
		</div>

		<section class="section">
			<div class="setion-header">
				<h3><?php esc_html_e( 'API Settings', 'askell-registration' ); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'Public API Key', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="api_key"
								value="<?php echo esc_attr( get_option( 'askell_api_key', '' ) ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Secret Key', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="api_secret"
								value="<?php echo esc_attr( get_option( 'askell_api_secret', '' ) ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th colspan="2">
							<?php esc_html_e( "Do not share the API secret with anyone as it is used for authentication between this WordPress site and Askell's services." ); ?>
						</th>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="section">
			<div class="setion-header">
				<h3><?php esc_html_e( 'Web Hooks', 'askell-registration' ); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Customer Webhook URL', 'askell-registration' ); ?>
						</th>
						<td>
							<?php echo esc_url( get_rest_url( null, $askell::REST_NAMESPACE . '/webhooks/customer' ) ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Subscription Webhook URL', 'askell-registration' ); ?>
						</th>
						<td>
							<?php echo esc_url( get_rest_url( null, $askell::REST_NAMESPACE . '/webhooks/subscription' ) ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Customer HMAC Secret', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="customer_webhook_secret"
								value="<?php echo esc_attr( get_option( 'askell_customer_webhook_secret', '' ) ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Subscription HMAC Secret', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="subscription_webhook_secret"
								value="<?php echo esc_attr( get_option( 'askell_subscription_webhook_secret', '' ) ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th colspan="2">
							<?php
							esc_html_e(
								'The HMAC secrets are used for cryptographically authenticating webhook requests coming from Askell and are created in the Askell interface. As with the API secret above, do not share those with anyone.',
								'askell-registration'
							);
							?>
						</th>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="section">
			<div class="setion-header">
				<h3><?php esc_html_e( 'URLs', 'askell-registration' ); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
						<?php esc_html_e( 'URL for Registration Form', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="register_url"
								value="<?php echo esc_attr( get_option( 'askell_register_url', '' ) ); ?>"
							/>
							<p class="description">
								<?php
								echo esc_html(
									'This is the url to the page where you have added the Askell Registration block. If unset, the ‘Register’ button on the paywall is disabled.',
									'askell-registration'
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'URL for Terms and Conditions', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="tos_url"
								value="<?php echo esc_attr( get_option( 'askell_tos_url', '' ) ); ?>"
							/>
							<p class="description">
								<?php
								echo esc_html(
									'This is the URL to where you keep your terms and conditions to be accepted during the registration process.',
									'askell-registration'
								)
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="section">
			<div class="setion-header">
				<h3><?php esc_html_e( 'Presentation', 'askell-registration' ); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Paywall Heading', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="paywall_heading"
								value="<?php echo esc_attr( get_option( 'askell_paywall_heading', $askell->default_paywall_heading ) ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Paywall Text Body', 'askell-registration' ); ?>
						</th>
						<td>
							<textarea class="regular-text" name="paywall_text_body" rows="5"><?php echo esc_attr( get_option( 'askell_paywall_text_body', $askell->default_paywall_text_body ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Styling and Design', 'askell-registration' ); ?>
						</th>
						<td>
							<label>
								<input
									type="checkbox"
									name="enable_css"
									<?php echo get_option( 'askell_styles_enabled', true ) ? 'checked' : ''; ?>
								>
								<?php esc_html_e( 'Enable built-in stylesheet', 'askell-registration' ); ?>
							</label>
							<p class="description">
								<?php
								esc_html_e(
									'Disable this to remove the additional CSS styles provided by Askell from the registration block. It may be useful for those who need to have full control of how their website is displayed.',
									'askell-registration'
								);
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<p class="submit">
			<img
				id="askell-settings-loader"
				class="hidden"
				src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
				width="32"
				height="32"
			/>
			<input type="submit" value="<?php esc_attr_e( 'Save Settings', 'askell-registration' ); ?>" class="button button-primary button-hero" />
		</p>
	</form>
</div>
