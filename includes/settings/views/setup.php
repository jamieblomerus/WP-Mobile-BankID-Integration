<?php
/**
 * Setup view on activation.
 *
 * @package mobile-bankid-integration
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$step = isset( $_GET['step'] ) ? $_GET['step'] : 1; // phpcs:ignore
$step = intval( $step );
?>
<div id="wizard-modal">
	<div id="wizard-modal-content">
		<div id="wizard-modal-content-inner">
			<h2><?php esc_html_e( 'Are you sure?', 'mobile-bankid-integration' ); ?></h2>
			<p id="wizard-modal-confirmation-text"></p>
		</div>
		<hr>
		<div id="wizard-modal-footer">
			<button class="button button-primary" id="wizard-modal-abort"><?php esc_html_e( 'Abort', 'mobile-bankid-integration' ); ?></button>
			<button class="button button-secondary" onclick="confirmconfirmation()" id="wizard-modal-confirm"><?php esc_html_e( 'Confirm', 'mobile-bankid-integration' ); ?></button>
		</div>
	</div>
</div>
<div class="wizard">
	<div class="steps">
		<ol>
			<li 
			<?php
			if ( $step > 1 ) {
				?>
class="done"
				<?php
			} else {
				?>
				class="active"<?php } ?>>
				<span class="title"><?php esc_html_e( 'Welcome', 'mobile-bankid-integration' ); ?></span>
			</li>
			<li 
			<?php
			if ( $step > 2 ) {
				?>
class="done"
				<?php
			} elseif ( 2 === $step ) {
				?>
class="active"<?php } ?>>
				<span class="title"><?php esc_html_e( 'Configuration', 'mobile-bankid-integration' ); ?></span>
			</li>
			<li 
			<?php
			if ( $step > 3 ) {
				?>
class="done"
				<?php
			} elseif ( 3 === $step ) {
				?>
class="active"<?php } ?>>
				<span class="title"><?php esc_html_e( 'Settings', 'mobile-bankid-integration' ); ?></span>
			</li>
			<li 
			<?php
			if ( 4 === $step ) {
				?>
class="active"<?php } ?>>
				<span class="title"><?php esc_html_e( 'Finish', 'mobile-bankid-integration' ); ?></span>
			</li>
		</ol>
	</div><br>

	<div id="wizard-content" step="<?php echo esc_attr( $step ); ?>">
		<?php
		switch ( $step ) {
			case 1:
				include_once 'setup-welcome.php';
				break;
			case 2:
				include_once 'setup-configuration.php';
				break;
			case 3:
				include_once 'setup-settings.php';
				break;
			case 4:
				include_once 'setup-finish.php';
				break;
			default:
				include_once 'setup-welcome.php';
				break;
		}
		?>
	</div>
	<p class="footer-info"><?php esc_html_e( 'Mobile BankID Integration version: ', 'mobile-bankid-integration' ); ?> <?php echo esc_html( MOBILE_BANKID_INTEGRATION_VERSION ); ?></p>
</div>