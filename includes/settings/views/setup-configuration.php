<h1><?php esc_html_e( 'Configuration', 'mobile-bankid-integration' ); ?></h1>
<p><?php esc_html_e( 'Let\'s configure the plugin for it to give that personal touch.', 'mobile-bankid-integration' ); ?></p>
<form autocomplete="off">
	<input autocomplete="false" type="text" name="mobile_bankid_integration_setup" value="1" style="display: none;">
	<h2><?php esc_html_e( 'Auto-configuration', 'mobile-bankid-integration' ); ?></h2>
	<div class="form-group">
		<label for="mobile-bankid-integration-testenv"><?php esc_html_e( 'Auto-configure for test enviroment', 'mobile-bankid-integration' ); ?></label>
		<input type="checkbox" id="mobile-bankid-integration-testenv">
		<p class="description"><?php esc_html_e( 'This will configure the plugin for the test enviroment. This is only recommended if you are testing the plugin.', 'mobile-bankid-integration' ); ?></p>
	</div>
	<h2><?php esc_html_e( 'Manual configuration', 'mobile-bankid-integration' ); ?></h2>
	<div class="form-group">
		<label for="mobile-bankid-integration-endpoint"><?php esc_html_e( 'API Endpoint', 'mobile-bankid-integration' ); ?></label>
		<input type="url" id="mobile-bankid-integration-endpoint">
		<p class="description">
		<?php
		printf(
			/* translators: %1$s Production API Endpoint, %1$s Test enviroment API Endpoint */
			__( 'The API Endpoint is normally %1$s for production and %2$s for test environment.', 'mobile-bankid-integration' ),
			'<code>https://appapi2.bankid.com/rp/v5.1</code>',
			'<code>https://appapi2.test.bankid.com/rp/v5.1</code>'
		)
		?>
			</p>
	</div>
	<div class="form-group">
		<label for="mobile-bankid-integration-certificate"><?php esc_html_e( 'Absolute path to certificate', 'mobile-bankid-integration' ); ?></label>
		<input type="text" id="mobile-bankid-integration-certificate" placeholder="<?php /* translators: Placeholder path to .p12 certificate. */ esc_attr_e( '/path/to/certificate.p12', 'mobile-bankid-integration' ); ?>">
		<p class="description"><?php esc_html_e( 'Please note that the certificate shall, for security reasons, not be placed within any publicly accessible directory.', 'mobile-bankid-integration' ); ?></p>
	</div>
	<div class="form-group">
		<label for="mobile-bankid-integration-password"><?php esc_html_e( 'Certificate password', 'mobile-bankid-integration' ); ?></label>
		<input type="password" id="mobile-bankid-integration-password" autocomplete="off" data-lpignore="true" >
	</div>
</form><br>
<button class="button button-primary" onclick="configureSubmit()" id="mobile-bankid-integration-setup"><?php esc_html_e( 'Next', 'mobile-bankid-integration' ); ?></button>