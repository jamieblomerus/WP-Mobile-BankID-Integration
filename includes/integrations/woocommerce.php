<?php
namespace Webbstart\WP_BankID\Integrations\WooCommerce;
use Webbstart\WP_BankID\Core;
use Personnummer\Personnummer;

new Settings;
new Login;
new Checkout;

final class Settings {
    function __construct() {
        add_filter( 'woocommerce_get_sections_advanced', [$this, 'addSettingsSection'] );
        add_filter( 'woocommerce_get_settings_advanced', [$this, 'addSettings'], 10, 2 );
    }

    public function addSettingsSection( $sections ) {
        $sections['wp_bankid'] = __( 'WP BankID', 'wp-bankid' );
        return $sections;
    }

    public function addSettings($settings, $current_section) {
        if ($current_section == 'wp_bankid') {
            $settings_wp_bankid = array();
            $settings_wp_bankid[] = array(
                'name' => __( 'WP BankID', 'wp-bankid' ),
                'type' => 'title',
                'desc' => '',
                'id' => 'wp_bankid'
            );
            /* Login using BankID */
            $settings_wp_bankid[] = array(
                'name' => __( 'Login using BankID', 'wp-bankid' ),
                'desc' => __( 'Let customers login using Mobile BankID on My Account page.', 'wp-bankid' ),
                'id' => 'wp_bankid_woocommerce_login',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'default' => 'no'
            );
            /* Require customer to be logged in with BankID at checkout */
            $settings_wp_bankid[] = array(
                'name' => __( 'Require users to be authenticated through Mobile BankID at checkout', 'wp-bankid' ),
                'desc' => __( 'Require customer to be logged in with BankID at checkout. This helps to follow the law regarding sale of age-restricted products.', 'wp-bankid' ),
                'id' => 'wp_bankid_woocommerce_checkout_require_bankid',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'default' => 'no'
            );
            /* Require customer to be over a certain age at checkout */
            $settings_wp_bankid[] = array(
                'name' => __( 'Require users to be over a certain age at checkout (0 to disable)', 'wp-bankid' ),
                'desc' => __( 'Require customer to be over a certain age at checkout. This helps to follow the law regarding sale of age-restricted products.<br>This requires that users are forced to sign in with BankID at checkout.', 'wp-bankid' ),
                'id' => 'wp_bankid_woocommerce_age_check',
                'type' => 'number',
                'css' => 'min-width:300px;',
                'default' => '18'
            );

            $settings_wp_bankid[] = array( 'type' => 'sectionend', 'id' => 'wcslider' );
            return $settings_wp_bankid;
        } else {
            return $settings;
        }
    }
}

class Login extends \Webbstart\WP_BankID\WP_Login\Login {
    function __construct() {
        if (get_option('wp_bankid_woocommerce_login') == "yes" && (get_option('wp_bankid_certificate') && get_option('wp_bankid_password') && get_option('wp_bankid_endpoint'))) {
            add_action('woocommerce_login_form_end', function() {
                $this->login_button("/my-account");
            });
        }
    }
}

class Checkout {
    public function __construct() {
        if (get_option('wp_bankid_woocommerce_checkout_require_bankid') != "yes") {
            return;
        }
        add_action('woocommerce_checkout_before_customer_details', array($this, 'checkout_block'));
        add_action('woocommerce_after_checkout_validation', array($this, 'validate'),10,2);
    }
    public function checkout_block() {
        if (Core::$instance->verifyAuthCookie() && get_option('wp_bankid_woocommerce_age_check', 0) <= 0) {
            return;
        } else if (Core::$instance->verifyAuthCookie()) {
            if ($this->age_check()) {
                return;
            } else {
                wc_add_notice( sprintf( __( 'You must be over %s years old to make an order.', 'wp-bankid' ), get_option('wp_bankid_woocommerce_age_check') ), 'error' );
                return;
            }
        }
        ?>
        <div id="bankid-checkout-block" style="background: #c1ced9; border-radius: 10px; padding:15px;">
            <div class="woocommerce-billing-fields">
                <h3><?php esc_html_e('Mobile BankID Authentication required', 'wp-bankid') ?></h3>
                <p><?php esc_html_e("This site requires you to be authenticated through Mobile BankID to make an order.") ?></p>

                <p><a href="#" id="bankid-login-button" class="button wp-element-button" style="text-align: center;"><?php esc_html_e('Login with BankID', 'wp-bankid')?></a></p>
                <p><?php esc_html_e('If you do not have Mobile BankID, you can download it from your bank.', 'wp-bankid') ?></p>
                <noscript>
                    <p><?php esc_html_e('This feature requires JavaScript. Please enable it.', 'wp-bankid') ?></p>
                    <style>#bankid-login-button { display: none; height: 0; margin: 0; }</style>
                </noscript>
                <?php
                // Load scripts
                $login = new \Webbstart\WP_BankID\WP_Login\Login;
                $login->load_scripts("/checkout");
                ?>
            </div>
        </div>
        <?php
    }
    public function age_check(): bool {
        $age = get_option('wp_bankid_woocommerce_age_check', 0);
        if ($age <= 0) {
            return true;
        }
        $personnummer = get_user_meta(get_current_user_id(), 'wp_bankid_personal_number', true);
        if (!$personnummer) {
            return false;
        }
        $userage = (new Personnummer($personnummer))->getAge();
        if ($userage < $age) {
            return false;
        }
        return true;
    }
    public function validate($data, $errors) {
        if (Core::$instance->verifyAuthCookie()) {
            if ($this->age_check()) {
                return;
            } else {
                $errors->add('bankid_error', sprintf( __( 'You must be over %s years old to make an order.', 'wp-bankid' ), get_option('wp_bankid_woocommerce_age_check') ));
                return;
            }
        }
        $errors->add('bankid_error', __('You must be authenticated through Mobile BankID to make an order.', 'wp-bankid'));
    }
}