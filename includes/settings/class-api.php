<?php
namespace Mobile_BankID_Integration\Settings;

new API;

class API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('wp-bankid/v1/settings', '/configuration', array(
            'methods' => 'POST',
            'callback' => [$this, 'configuration'],
            'permission_callback' => [$this, 'haveRights'],
        ));
        register_rest_route('wp-bankid/v1/settings', '/autoconfiguretestenv', array(
            'methods' => 'GET',
            'callback' => [$this, 'auto_configure_test_env'],
            'permission_callback' => [$this, 'haveRights'],
        ));
        register_rest_route('wp-bankid/v1/settings', '/setup_settings', array(
            'methods' => 'POST',
            'callback' => [$this, 'setup_settings'],
            'permission_callback' => [$this, 'haveRights'],
        ));
        register_rest_route('wp-bankid/v1/settings', '/settings', array(
            'methods' => 'POST',
            'callback' => [$this, 'settings'],
            'permission_callback' => [$this, 'haveRights'],
        ));
    }

    function haveRights() {
        return current_user_can('manage_options');
    }

    public function configuration() {
        // Check endpoint domain is one of the allowed endpoints
        if (!preg_match("/^https:\/\/appapi2\.(test\.)?bankid\.com\/rp\/v5\.1$/", $_POST["endpoint"])) {
            var_dump($_POST["endpoint"]);
            return new \WP_Error('invalid_endpoint', esc_html__('API Endpoint is not valid.', 'wp-bankid'), array('status' => 400));
        }

        // Check that submitted certificate is valid and exists
        if (!preg_match("/^\/([A-z0-9-_+]+\/)*([A-z0-9]+\.(p12))$/", $_POST["certificate"])) {
            return new \WP_Error('invalid_certificate', esc_html__('Certificate is not valid.', 'wp-bankid'), array('status' => 400));
        }
        if (!file_exists($_POST["certificate"])) {
            return new \WP_Error('certificate_does_not_exist', esc_html__('Certificate does not exist on specified path.', 'wp-bankid'), array('status' => 400));
        }

        // Check that password is not empty and is longer than 12 characters
        if (empty($_POST["password"])) {
            return new \WP_Error('empty_password', esc_html__('Password is empty.', 'wp-bankid'), array('status' => 400));
        }
        if (strlen($_POST["password"]) < 12 && $_POST["password"] != 'qwerty123') {
            return new \WP_Error('short_password', esc_html__('Password must be longer than 12 characters.', 'wp-bankid'), array('status' => 400));
        }

        // Check that password contains atleast 4 letters and 1 number, per BankID docs
        if (preg_match_all( "/[A-z]/", $_POST["password"] ) < 4 || preg_match_all( "/[0-9]/", $_POST["password"] ) < 1) {
            return new \WP_Error('password_format', esc_html__('Password must have atleast 4 letters and 1 number.', 'wp-bankid'), array('status' => 400));
        }

        //TODO: Check that password is valid, test it against the certificate

        // Update the WP options.
        update_option('mobile_bankid_integration_certificate', $_POST["certificate"]);
        update_option('mobile_bankid_integration_endpoint', $_POST["endpoint"]);
        update_option('mobile_bankid_integration_password', $_POST["password"]);

        return true;
    }

    public function auto_configure_test_env() {
        // Check if certificate exists
        $certificate_dir = MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'assets/certs/';
        if (!file_exists($certificate_dir . 'testenv.p12')) {
            return new \WP_Error('certificate_does_not_exist', esc_html__('Certificate does not exist.', 'wp-bankid'), array('status' => 400));
        }

        // Update the WP options.
        update_option('mobile_bankid_integration_certificate', $certificate_dir . 'testenv.p12');
        update_option('mobile_bankid_integration_endpoint', 'https://appapi2.test.bankid.com/rp/v5.1/');
        update_option('mobile_bankid_integration_password', 'qwerty123');

        return true;
    }

    public function setup_settings() {
        // Make sure that the submitted values are valid
        if (!in_array($_POST["wplogin"], array('as_alternative', 'hide'))) {
            return new \WP_Error('invalid_wplogin', esc_html__('Invalid value for wplogin.', 'wp-bankid'), array('status' => 400));
        }
        if (!in_array($_POST["registration"], array('yes', 'no'))) {
            return new \WP_Error('invalid_registration', esc_html__('Invalid value for registration.', 'wp-bankid'), array('status' => 400));
        }

        // Update the WP options.
        update_option('mobile_bankid_integration_wplogin', $_POST["wplogin"]);
        update_option('mobile_bankid_integration_registration', $_POST["registration"]);
        return true;
    }

    public function settings() {
        if (!in_array($_POST["wplogin"], array('as_alternative', 'hide'))) {
            return new \WP_Error('invalid_wplogin', esc_html__('Invalid value for wplogin.', 'wp-bankid'), array('status' => 400));
        }

        if (!in_array($_POST["registration"], array('yes', 'no'))) {
            return new \WP_Error('invalid_registration', esc_html__('Invalid value for registration.', 'wp-bankid'), array('status' => 400));
        }

        // Update the WP options.
        update_option('mobile_bankid_integration_wplogin', $_POST["wplogin"]);
        update_option('mobile_bankid_integration_registration', $_POST["registration"]);
        update_option('mobile_bankid_integration_terms', $_POST["terms"]);
    }
}