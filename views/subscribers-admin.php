<?php
/**
 * The main admin view
 *
 * @package askell-registration
 */

$askell_registration = new AskellRegistration();
$askell_user_query   = new WP_User_Query(
	array(
		'role'    => 'subscriber',
		'orderby' => 'ID',
	)
);

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
				'The Askell API key and Shared Secret values have not been set yet. Please open the Settings pane and enter the missing values.',
				'askell-registration'
			);
			?>
		</p>
	</div>
	<?php endif ?>

	<h1>
		<?php esc_html_e( 'Askell for WordPress', 'askell-registration' ); ?>
	</h1>

	<div id="askell-registration-users">
		<h2><?php esc_html_e( 'Subscribers', 'askell-registration' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="row"><?php esc_html_e( 'Name', 'askell-registration' ); ?></th>
					<th scope="row"><?php esc_html_e( 'Username', 'askell-registration' ); ?></th>
					<th scope="row"><?php esc_html_e( 'Email Address', 'askell-registration' ); ?></th>
					<th scope="row"><?php esc_html_e( 'Active Plans', 'askell-registration' ); ?></th>
					<th scope="row"><?php esc_html_e( 'Sign-Up Date', 'askell-registration' ); ?></th>
					<th scope="row" class="actions"><?php esc_html_e( 'Actions', 'askell-registration' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $askell_user_query->get_results() as $u ) : ?>
				<tr>
					<th scope="col" class="column-title column-primary">
						<strong>
							<?php if ( '' === $u->askell_customer_id ) : ?>
							<a href="<?php echo esc_url( get_admin_url() . "user-edit.php?user_id={$u->ID}" ); ?>">
								<?php echo esc_attr( $u->display_name ); ?>
							</a>
							<?php else : ?>
							<a
								href="https://askell.is/dashboard/customers/<?php echo esc_attr( $u->askell_customer_id ); ?>/"
								target="_blank"
							>
								<?php echo esc_attr( $u->display_name ); ?>
							</a>
							<?php endif ?>
						</strong>
					</th>
					<td><?php echo esc_html( $u->user_login ); ?></td>
					<td><a href="mailto:<?php echo esc_attr( $u->user_email ); ?>"><?php echo esc_attr( $u->user_email ); ?></a></td>

					<td>
						<?php echo esc_html( $askell_registration->plan_names_for_user( $u ) ); ?>
					</td>

					<td><?php echo esc_html( $u->user_registered ); ?></td>
					<td>
						<a
							class="button"
							href="<?php echo esc_url( get_admin_url() . "user-edit.php?user_id={$u->ID}" ); ?>"
						>
							<?php echo esc_html_e( 'Manage in WP', 'askell-registration' ); ?>
						</a>
						<?php if ( '' !== $u->askell_customer_id ) : ?>
						<a
							class="button"
							href="https://askell.is/dashboard/customers/<?php echo esc_attr( $u->askell_customer_id ); ?>/"
							target="_blank"
						>
							<?php echo esc_html_e( 'Manage in Askell', 'askell-registration' ); ?>
						</a>
						<?php endif ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
