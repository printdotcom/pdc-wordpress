<?php
/**
 * Credentials settings section partial
 *
 * Renders the settings fields for API credentials and environment.
 *
 * @package Pdc_Pod
 * @subpackage Admin\Partials
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$pdc_pod_api_key = get_option( PDC_POD_NAME . '-api_key' );
$pdc_pod_env     = get_option( PDC_POD_NAME . '-env' );
$pdc_pod_app_url = ( 'prod' === $pdc_pod_env ) ? 'app.print.com' : 'app.stg.print.com';
?>

<p>
	<?php esc_html_e( 'You can create an API key in your Print.com account settings. Visit', 'pdc-pod' ); ?>
	<a data-testid="pdc-pod-environment-link" target="_blank" href="<?php echo esc_url( 'https://' . $pdc_pod_app_url . '/account' ); ?>">
		<?php echo esc_html( $pdc_pod_app_url . '/account' ); ?>
	</a>,
	<?php esc_html_e( 'create an API key and paste it in the input field below.', 'pdc-pod' ); ?>
</p>

<div class="notice notice-success hidden" id="js-<?php echo esc_attr( PDC_POD_NAME ); ?>-auth-success">
	<p><?php esc_html_e( 'API Key verified. You are now connected!', 'pdc-pod' ); ?></p>
</div>
<div class="notice notice-error hidden" id="js-<?php echo esc_attr( PDC_POD_NAME ); ?>-auth-failed">
	<p><?php esc_html_e( 'API Key is not valid. Check your environment and API Key', 'pdc-pod' ); ?></p>
</div>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="pdc_pod_api_key"><?php esc_html_e( 'API Key', 'pdc-pod' ); ?></label></th>
			<td>
				<input id="pdc_pod_api_key" data-testid="pdc-pod-apikey" name="<?php echo esc_attr( PDC_POD_NAME ); ?>-api_key" type="text" value="<?php echo esc_attr( $pdc_pod_api_key ); ?>" class="regular-text" />
				<span id="js-<?php echo esc_attr( PDC_POD_NAME ); ?>-verify_loader" class="spinner"></span>
				<button data-testid="pdc-pod-verify-key" type="button" id="js-<?php echo esc_attr( PDC_POD_NAME ); ?>-verify_key" class="button button-secondary">
					<span><?php esc_html_e( 'Verify', 'pdc-pod' ); ?></span>
				</button>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pdc_pod_env"><?php esc_html_e( 'Environment', 'pdc-pod' ); ?></label></th>
			<td>
				<select data-testid="pdc-pod-environment" name="<?php echo esc_attr( PDC_POD_NAME ); ?>-env" id="pdc_pod_env">
					<option value="stg" <?php selected( $pdc_pod_env, 'stg' ); ?>><?php esc_html_e( 'Test', 'pdc-pod' ); ?></option>
					<option value="prod" <?php selected( $pdc_pod_env, 'prod' ); ?>><?php esc_html_e( 'Live', 'pdc-pod' ); ?></option>
				</select>
			</td>
		</tr>
	</tbody>
</table>
