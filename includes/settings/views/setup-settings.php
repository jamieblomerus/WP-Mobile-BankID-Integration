<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<h1><?php esc_html_e( 'Settings', 'mobile-bankid-integration' ); ?></h1>
<p><?php esc_html_e( 'Almost finished, just a couple last settings to suite your needs.', 'mobile-bankid-integration' ); ?></p>
<form autocomplete="off">
	<h2><?php esc_html_e( 'Login page', 'mobile-bankid-integration' ); ?></h2>
	<div class="form-group">
		<label for="mobile-bankid-integration-wplogin"><?php esc_html_e( 'Show BankID on WordPress login page (wp-login.php)', 'mobile-bankid-integration' ); ?></label>
		<select name="mobile-bankid-integration-wplogin" id="mobile-bankid-integration-wplogin">
			<option value="as_alternative" 
			<?php
			if ( get_option( 'mobile_bankid_integration_wplogin' ) === 'as_alternative' ) {
				echo 'selected'; }
			?>
			><?php esc_html_e( 'Show as alternative to traditional login', 'mobile-bankid-integration' ); ?></option>
			<option value="hide" 
			<?php
			if ( get_option( 'mobile_bankid_integration_wplogin' ) === 'hide' ) {
				echo 'selected'; }
			?>
			><?php esc_html_e( 'Do not show at all', 'mobile-bankid-integration' ); ?></option>
		</select>
	</div>
	<div class="form-group">
		<label for="mobile-bankid-integration-registration"><?php esc_html_e( 'Allow registration with BankID', 'mobile-bankid-integration' ); ?></label>
		<select name="mobile-bankid-integration-registration" id="mobile-bankid-integration-registration">
			<option value="yes" 
			<?php
			if ( get_option( 'mobile_bankid_integration_registration' ) === 'yes' ) {
				echo 'selected'; }
			?>
			><?php esc_html_e( 'Yes', 'mobile-bankid-integration' ); ?></option>
			<option value="no" 
			<?php
			if ( get_option( 'mobile_bankid_integration_registration' ) === 'no' ) {
				echo 'selected'; }
			?>
			><?php esc_html_e( 'No', 'mobile-bankid-integration' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'This setting does not affect, nor is affected by, the native "Allow registration" setting.', 'mobile-bankid-integration' ); ?></p>
	</div>
</form><br>
<button class="button button-primary" onclick="settingsSubmit()" id="mobile-bankid-integration-setup"><?php esc_html_e( 'Next', 'mobile-bankid-integration' ); ?></button>