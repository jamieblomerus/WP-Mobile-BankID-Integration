<h1><?php esc_html_e('Settings', 'wp-bankid'); ?></h1>
<p><?php esc_html_e('Almost finished, just a couple last settings to suite your needs.', 'wp-bankid'); ?></p>
<form autocomplete="off">
    <h2><?php esc_html_e('Login page', 'wp-bankid'); ?></h2>
    <div class="form-group">
        <label for="wp-bankid-wplogin"><?php esc_html_e('Show BankID on WordPress login page (wp-login.php)', 'wp-bankid'); ?></label>
        <select name="wp-bankid-wplogin" id="wp-bankid-wplogin">
            <option value="as_alternative" <?php if (get_option('mobile_bankid_integration_wplogin') == "as_alternative") { echo 'selected'; } ?>><?php esc_html_e('Show as alternative to traditional login', 'wp-bankid'); ?></option>
            <option value="hide" <?php if (get_option('mobile_bankid_integration_wplogin') == "hide") { echo 'selected'; } ?>><?php esc_html_e('Do not show at all', 'wp-bankid'); ?></option>
        </select>
    </div>
    <div class="form-group">
        <label for="wp-bankid-registration"><?php esc_html_e('Allow registration with BankID', 'wp-bankid'); ?></label>
        <select name="wp-bankid-registration" id="wp-bankid-registration">
            <option value="yes" <?php if (get_option('mobile_bankid_integration_registration') == "yes") { echo 'selected'; } ?>><?php esc_html_e('Yes', 'wp-bankid'); ?></option>
            <option value="no" <?php if (get_option('mobile_bankid_integration_registration') == "no") { echo 'selected'; } ?>><?php esc_html_e('No', 'wp-bankid'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('This setting does not affect, nor is affected by, the native "Allow registration" setting.', 'wp-bankid'); ?></p>
    </div>
</form><br>
<button class="button button-primary" onclick="settingsSubmit()" id="wp-bankid-setup"><?php esc_html_e('Next', 'wp-bankid'); ?></button>