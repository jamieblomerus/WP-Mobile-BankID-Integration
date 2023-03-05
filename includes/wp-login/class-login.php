<?php
namespace Webbstart\WP_BankID\WP_Login;

new Login;

class Login {
    public function __construct() {
        if (get_option('wp_bankid_wplogin') == "as_alternative" && (get_option('wp_bankid_certificate') && get_option('wp_bankid_password') && get_option('wp_bankid_endpoint'))) {
            add_action('login_form', array($this, 'login_button'), 40);
        }
    }
    public function login_button() {
        echo '<p><a href="#" id="bankid-login-button" class="button" style="width: 100%; text-align: center;">'.esc_html__('Login with BankID', 'wp-bankid').'</a></p><br>';
        echo '<noscript><style>#bankid-login-button { display: none; height: 0; margin: 0; }</style></noscript>';
        wp_register_script('wp-bankid-login', WP_BANKID_PLUGIN_URL . 'assets/js/login.js', array('jquery'), WP_BANKID_VERSION, true);
        wp_enqueue_script('wp-bankid-login');
        wp_enqueue_script('jquery');
        wp_localize_script('wp-bankid-login', 'wp_bankid_login_localization', [
            'title' => esc_html__('Login with BankID', 'wp-bankid'),
            'qr_instructions' => esc_html__('Scan the QR code with your Mobile BankID app.', 'wp-bankid'),
            'qr_alt' => esc_html__('QR code', 'wp-bankid'),
            'cancel' => esc_html__('Cancel', 'wp-bankid'),
            'open_on_this_device' => esc_html__('Start the BankID app', 'wp-bankid'),
            'status_expired' => esc_html__('BankID identification session has expired. Please try again.', 'wp-bankid'),
            'status_complete' => esc_html__('BankID identification completed. Redirecting...', 'wp-bankid'),
            'status_complete_no_user' => esc_html__('BankID identification completed, but no user was found. Please try again.', 'wp-bankid'),
            'status_failed' => esc_html__('BankID identification failed. Please try again.', 'wp-bankid'),
            'something_went_wrong' => esc_html__('Something went wrong. Please try again.', 'wp-bankid'),

            // BankID Hint Code with translation note
            'hintcode_userCancel' => esc_html__('Action cancelled.', 'wp-bankid'),
            'hintcode_userSign' => esc_html__('Enter your security code in the BankID app and select Identify.', 'wp-bankid'),
            'hintcode_startFailed' => esc_html__("Failed to scan the QR code.", 'wp-bankid'),
            'hintcode_certificateErr' => esc_html__('The BankID you are trying to use is revoked or too old. Please use another BankID or order a new one from your internet bank.', 'wp-bankid'),
        ]);
        wp_add_inline_script('wp-bankid-login', 'var wp_bankid_rest_api = "' . rest_url('wp-bankid/v1/login') . '"; var wp_bankid_redirect_url = "/wp-admin/";', 'before');
    }
}