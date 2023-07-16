<h1><?php esc_html_e('Configuration', 'wp-bankid'); ?></h1>
<p><?php esc_html_e('Let\'s configure the plugin for it to give that personal touch.', 'wp-bankid'); ?></p>
<form autocomplete="off">
    <input autocomplete="false" type="text" name="mobile_bankid_integration_setup" value="1" style="display: none;">
    <h2><?php esc_html_e('Auto-configuration', 'wp-bankid'); ?></h2>
    <div class="form-group">
        <label for="wp-bankid-testenv"><?php esc_html_e('Auto-configure for test enviroment', 'wp-bankid'); ?></label>
        <input type="checkbox" id="wp-bankid-testenv">
        <p class="description"><?php esc_html_e('This will configure the plugin for the test enviroment. This is only recommended if you are testing the plugin.', 'wp-bankid'); ?></p>
    </div>
    <h2><?php esc_html_e('Manual configuration', 'wp-bankid'); ?></h2>
    <div class="form-group">
        <label for="wp-bankid-endpoint"><?php esc_html_e('API Endpoint', 'wp-bankid'); ?></label>
        <input type="url" id="wp-bankid-endpoint">
        <p class="description"><?php printf(
            /* translators: %1$s Production API Endpoint, %1$s Test enviroment API Endpoint */
            __('The API Endpoint is normally %1$s for production and %2$s for test environment.', 'wp-bankid'),
            '<code>https://appapi2.bankid.com/rp/v5.1</code>',
            '<code>https://appapi2.test.bankid.com/rp/v5.1</code>'
            ) ?></p>
    </div>
    <div class="form-group">
        <label for="wp-bankid-certificate"><?php esc_html_e('Absolute path to certificate', 'wp-bankid'); ?></label>
        <input type="text" id="wp-bankid-certificate" placeholder="<?php /* translators: Placeholder path to .p12 certificate. */ esc_attr_e('/path/to/certificate.p12', 'wp-bankid'); ?>">
        <p class="description"><?php esc_html_e('Please note that the certificate shall, for security reasons, not be placed within any publicly accessible directory.', 'wp-bankid'); ?></p>
    </div>
    <div class="form-group">
        <label for="wp-bankid-password"><?php esc_html_e('Certificate password', 'wp-bankid'); ?></label>
        <input type="password" id="wp-bankid-password" autocomplete="off" data-lpignore="true" >
    </div>
</form><br>
<button class="button button-primary" onclick="configureSubmit()" id="wp-bankid-setup"><?php esc_html_e('Next', 'wp-bankid'); ?></button>