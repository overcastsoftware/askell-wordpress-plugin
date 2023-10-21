<?php

$askell_registration = new AskellRegistration();

$askell_user_query_arguments = array( 'role' => 'subscriber' );
$askell_user_query = new WP_User_Query( $askell_user_query_arguments );

?>

<div class="wrap">
	<div class="notice notice-warning">
		<p><?php _e('This is an early development version of Askell for WordPress. Do not use this version of the plugin on a production website!', 'askell-registration'); ?></p>
	</div>

	<?php if ( empty ( get_option( 'askell_api_key', '' ) ) || empty( get_option( 'askell_api_secret', '' ) ) ) : ?>
	<div class="notice notice-error">
		<p><?php _e('The Askell API key and Shared Secret values have not been set yet. Please open the Settings pane and enter the missing values.', 'askell-registration'); ?></p>
	</div>
	<?php endif ?>

	<h1>
		<?php _e('Askell for WordPress', 'askell-registration'); ?>
	</h1>

	<nav class="nav-tab-wrapper">
		<a
			id="askell-nav-tab-users"
			class="nav-tab nav-tab-active"
			href="#"
		>
			<?php _e('Subscribers', 'askell-registration'); ?>
		</a>
		<a
			id="askell-nav-tab-settings"
			class="nav-tab"
			href="#"
		>
			<?php _e('Settings', 'askell-registration') ?>
		</a>
	</nav>

	<div id="askell-registration-users">
		<h2><?php _e('Subscribers', 'askell-registration'); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="row"><?php _e('Name', 'askell-registration'); ?></th>
					<th scope="row"><?php _e('Customer Reference', 'askell-registration'); ?></th>
					<th scope="row"><?php _e('Username', 'askell-registration'); ?></th>
					<th scope="row"><?php _e('Email Address', 'askell-registration'); ?></th>
					<th scope="row"><?php _e('Plan', 'askell-registration'); ?></th>
					<th scope="row"><?php _e('Registration Status', 'askell-registration'); ?></th>
					<th scope="row"><?php _e('Sign-Up Date', 'askell-registration'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($askell_user_query->get_results() as $u) : ?>
				<tr>
					<th scope="col" class="column-title column-primary">
						<strong>
							<a href="<?php echo get_admin_url() . "user-edit.php?user_id={$u->ID}"; ?>">
								<?php echo $u->display_name; ?>
							</a>
						</strong>
					</th>
					<td><?php echo $u->ID; ?></td>
					<td><?php echo $u->user_login; ?></td>
					<td><a href="mailto:<?php echo $u->user_email ?>"><?php echo $u->user_email ?></a></td>

					<td><?php echo get_user_meta($u->ID, 'askell_plan_id', true) ?></td>
					<td><?php echo get_user_meta($u->ID, 'askell_registration_status', true) ?></td>

					<td><?php echo $u->user_registered ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<form action="#" class="type-form hidden" id="askell-registration-settings">
		<div>
			<h2 class="hidden"><?php _e('Settings', 'askell-registration'); ?></h2>
		</div>

		<section class="section">
			<div class="setion-header">
				<h3><?php _e('API Settings', 'askell-registration'); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
						<?php _e('Public API Key', 'askell-registration'); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="api_key"
								value="<?php echo get_option( 'askell_api_key', '' ) ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e('Secret Key', 'askell-registration'); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="api_secret"
								value="<?php echo get_option( 'askell_api_secret', '' ) ?>"
							/>
							<p><?php _e('Do not share this key with anyone!', 'askell-registration'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="section">
			<div class="setion-header">
				<h3><?php _e('Web Hooks', 'askell-registration'); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php _e( 'Customer Webhook URL', 'askell-registration' ); ?>
						</th>
						<td>
							<?php echo get_rest_url( null, $askell_registration::REST_NAMESPACE . '/webhooks/customer' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Subscription Webhook URL', 'askell-registration' ); ?>
						</th>
						<td>
							<?php echo get_rest_url( null, $askell_registration::REST_NAMESPACE . '/webhooks/subscription' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Customer HMAC Secret', 'askell-registration' ); ?>
						</th>
						<td>
							<input
								class="regular-text"
								type="text"
								name="customer_webhook_secret"
								value="<?php echo get_option( 'askell_customer_webhook_secret', '' ) ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e( 'Subscription HMAC Secret', 'askell-registration' ); ?>
						</th>
						<td>
								<input
								class="regular-text"
								type="text"
								name="subscription_webhook_secret"
								value="<?php echo get_option( 'askell_subscription_webhook_secret', '' ) ?>"
							/>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="section">
			<div class="setion-header">
				<h3><?php _e('Presentation', 'askell-registration'); ?></h3>
				<hr />
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php _e('Styling and Design', 'askell-registration'); ?>
						</th>
						<td>
							<label>
								<input
									type="checkbox"
									name="enable_css"
									<?php echo get_option('askell_styles_enabled', true) ? 'checked' : '' ?>
								>
								<?php _e('Enable built-in stylesheet', 'askell-registration'); ?>
							</label>
							<p><?php _e('Disable this to remove the additional CSS styles provided by Askell from the registration block. It may be useful for those who need to have full control of how their website is displayed.', 'askell-registration'); ?></p>
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
			<input type="submit" value="<?php _e('Save Settings', 'askell-registration') ?>" class="button button-primary button-hero" />
		</p>
	</form>
</div>
