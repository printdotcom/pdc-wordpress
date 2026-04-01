<?php
/**
 * Support settings section partial
 *
 * Renders the support section with log level configuration and log download functionality.
 *
 * @package Pdc_Pod
 * @subpackage Admin\Partials
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$pdc_pod_loglevel = get_option( PDC_POD_NAME . '-loglevel', 'error' );
?>

<p>
	<?php esc_html_e( 'If you are experiencing issues, you can download the plugin log file below. Share this file with Print.com support to help diagnose the problem.', 'pdc-pod' ); ?>
</p>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="pdc_pod_loglevel"><?php esc_html_e( 'Log level', 'pdc-pod' ); ?></label></th>
			<td>
				<select name="<?php echo esc_attr( PDC_POD_NAME ); ?>-loglevel" id="pdc_pod_loglevel">
					<option value="none" <?php selected( $pdc_pod_loglevel, 'none' ); ?>><?php esc_html_e( 'None', 'pdc-pod' ); ?></option>
					<option value="error" <?php selected( $pdc_pod_loglevel, 'error' ); ?>><?php esc_html_e( 'Errors only', 'pdc-pod' ); ?></option>
					<option value="debug" <?php selected( $pdc_pod_loglevel, 'debug' ); ?>><?php esc_html_e( 'Debug', 'pdc-pod' ); ?></option>
				</select>
				<button type="button" id="js-<?php echo esc_attr( PDC_POD_NAME ); ?>-download-logs" class="button button-secondary">
					<?php esc_html_e( 'Download logs', 'pdc-pod' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Set to "Debug" to capture detailed information. Switch back to "Errors only" or "None" after troubleshooting.', 'pdc-pod' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>
