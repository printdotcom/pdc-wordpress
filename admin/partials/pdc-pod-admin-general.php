<?php
/**
 * Admin general settings page
 *
 * Renders the Print.com general settings admin page.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/partials
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<p><?php esc_html_e( 'This plugin allows you to connect your WooCommerce store to Print.com.', 'pdc-pod' ); ?></p>
	<form method="post" action="options.php" id="js-<?php echo esc_attr( PDC_POD_NAME ); ?>-general-form">
		<?php
		settings_fields( PDC_POD_NAME . '-options' );
		do_settings_sections( PDC_POD_NAME );
		submit_button(
			'Save Settings',
			'primary',
			'submit',
			true,
			array(
				'test-id' => 'pdc-save-settings',
			)
		);
		?>
	</form>
</div>