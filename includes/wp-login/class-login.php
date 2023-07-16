<?php
namespace Mobile_BankID_Integration\WP_Login;

new Login;

class Login {
    public function __construct() {
        if (get_option('mobile_bankid_integration_wplogin') == "as_alternative" && (get_option('mobile_bankid_integration_certificate') && get_option('mobile_bankid_integration_password') && get_option('mobile_bankid_integration_endpoint'))) {
            add_action('login_form', array($this, 'login_button'), 40);
            add_action('login_footer', function(){
                $this->terms();
            }, 40);
        }
    }
    public function login_button($redirect = null) {
        if ($redirect == null) {
            $redirect = "/wp-admin/";
        }
        echo '<p><a href="#" target="_self" id="bankid-login-button" class="button wp-element-button" style="width: 100%; text-align: center; margin-bottom: 1em;">'.esc_html__('Login with BankID', 'mobile-bankid-integration').'</a></p>';
        echo '<noscript><style>#bankid-login-button { display: none; height: 0; margin: 0; }</style></noscript>';
        $this->load_scripts($redirect);
    }
    public function terms(float $font_size = 0.7) {
        if (get_option('mobile_bankid_integration_terms') == "") {
            return;
        }
        ?>
        <p class="bankid-terms">
            <?php
            echo wp_kses(
                get_option('mobile_bankid_integration_terms', esc_html__("By logging in using Mobile BankID you agree to our Terms of Service and Privacy Policy.")),
                array(
                    'a'      => array(
                        'href'  => array(),
                        'title' => array(),
                        'target' => array(),
                    ),
                    'br'     => array(),
                    'em'     => array(),
                    'strong' => array(),
                    'i'      => array(),
                )
            ); ?>
        </p>
        <style>
            .bankid-terms {
                text-align: center;
                font-size: <?php echo(strval($font_size)) ?>rem;
                margin-top: 0;
                padding-top: 0;
            }
        </style>
        <?php
    }
    public function load_scripts(string $redirect) { // If the messages are updated, remember to update it in woocommerce.php as well
        wp_register_script('mobile-bankid-integration-login', MOBILE_BANKID_INTEGRATION_PLUGIN_URL . 'assets/js/login.js', array('jquery'), MOBILE_BANKID_INTEGRATION_VERSION, true);
        wp_enqueue_script('mobile-bankid-integration-login');
        wp_enqueue_script('jquery');
        wp_localize_script('mobile-bankid-integration-login', 'mobile_bankid_integration_login_localization', [
            'title' => esc_html__('Login with BankID', 'mobile-bankid-integration'),
            'qr_instructions' => esc_html__('Scan the QR code with your Mobile BankID app.', 'mobile-bankid-integration'),
            'qr_alt' => esc_html__('QR code', 'mobile-bankid-integration'),
            'cancel' => esc_html__('Cancel', 'mobile-bankid-integration'),
            'open_on_this_device' => esc_html__('Start the BankID app', 'mobile-bankid-integration'),
            'status_expired' => esc_html__('BankID identification session has expired. Please try again.', 'mobile-bankid-integration'),
            'status_complete' => esc_html__('BankID identification completed. Redirecting...', 'mobile-bankid-integration'),
            'status_complete_no_user' => esc_html__('BankID identification completed, but no user was found. Please try again.', 'mobile-bankid-integration'),
            'status_failed' => esc_html__('BankID identification failed. Please try again.', 'mobile-bankid-integration'),
            'something_went_wrong' => esc_html__('Something went wrong. Please try again.', 'mobile-bankid-integration'),

            // BankID Hint Code with translation note
            'hintcode_userCancel' => esc_html__('Action cancelled.', 'mobile-bankid-integration'),
            'hintcode_userSign' => esc_html__('Enter your security code in the BankID app and select Identify.', 'mobile-bankid-integration'),
            'hintcode_startFailed' => esc_html__("Failed to scan the QR code.", 'mobile-bankid-integration'),
            'hintcode_certificateErr' => esc_html__('The BankID you are trying to use is revoked or too old. Please use another BankID or order a new one from your internet bank.', 'mobile-bankid-integration'),
        ]);
        wp_add_inline_script('mobile-bankid-integration-login', 'var mobile_bankid_integration_rest_api = "' . rest_url('mobile-bankid-integration/v1/login') . '"; var mobile_bankid_integration_redirect_url = "' . $redirect . '";', 'before');
    }
}