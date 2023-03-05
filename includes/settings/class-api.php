<?php
namespace Webbstart\WP_BankID\Settings;

new API;

class API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('wp-bankid/v1/settings', '/configuration', array(
            'methods' => 'POST',
            'callback' => [$this, 'configuration'],
        ));
        register_rest_route('wp-bankid/v1/settings', '/autoconfiguretestenv', array(
            'methods' => 'GET',
            'callback' => [$this, 'auto_configure_test_env'],
        ));
        register_rest_route('wp-bankid/v1/settings', '/setup_settings', array(
            'methods' => 'POST',
            'callback' => [$this, 'setup_settings'],
        ));
    }

    public function configuration() {
        // Check if user is administator.
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', esc_html__('You do not have permission to access this resource.', 'wp-bankid'), array('status' => 401));
        }

        // Check endpoint domain is one of the allowed endpoints
        if (!preg_match("/^https:\/\/appapi2\.(test\.)?bankid\.com\/rp\/v5\.1$/", $_POST["endpoint"])) {
            var_dump($_POST["endpoint"]);
            return new \WP_Error('invalid_endpoint', esc_html__('API Endpoint is not valid.', 'wp-bankid'), array('status' => 401));
        }

        // Check that submitted certificate is valid and exists
        if (!preg_match("/^\/([A-z0-9-_+]+\/)*([A-z0-9]+\.(p12))$/", $_POST["certificate"])) {
            return new \WP_Error('invalid_certificate', esc_html__('Certificate is not valid.', 'wp-bankid'), array('status' => 401));
        }
        if (!file_exists($_POST["certificate"])) {
            return new \WP_Error('certificate_does_not_exist', esc_html__('Certificate does not exist on specified path.', 'wp-bankid'), array('status' => 401));
        }

        // Check that password is not empty and is longer than 12 characters
        if (empty($_POST["password"])) {
            return new \WP_Error('empty_password', esc_html__('Password is empty.', 'wp-bankid'), array('status' => 401));
        }
        if (strlen($_POST["password"]) < 12 && $_POST["password"] != 'qwerty123') {
            return new \WP_Error('short_password', esc_html__('Password must be longer than 12 characters.', 'wp-bankid'), array('status' => 401));
        }

        // Check that password contains atleast 4 letters and 1 number, per BankID docs
        if (preg_match_all( "/[A-z]/", $_POST["password"] ) < 4 || preg_match_all( "/[0-9]/", $_POST["password"] ) < 1) {
            return new \WP_Error('password_format', esc_html__('Password must have atleast 4 letters and 1 number.', 'wp-bankid'), array('status' => 401));
        }

        //TODO: Check that password is valid, test it against the certificate

        // Update the WP options.
        update_option('wp_bankid_certificate', $_POST["certificate"]);
        update_option('wp_bankid_endpoint', $_POST["endpoint"]);
        update_option('wp_bankid_password', $_POST["password"]);

        return true;
    }

    public function auto_configure_test_env() {
        // Check if user is administator.
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', esc_html__('You do not have permission to access this resource.', 'wp-bankid'), array('status' => 401));
        }

        // Check if certificate exists
        $certificate_dir = WP_BANKID_PLUGIN_DIR . 'assets/certs/';
        if (!file_exists($certificate_dir . 'testenv.p12')) {
            return new \WP_Error('rest_forbidden', esc_html__('Certificate does not exist.', 'wp-bankid'), array('status' => 401));
        }

        // Update the WP options.
        update_option('wp_bankid_certificate', $certificate_dir . 'testenv.p12');
        update_option('wp_bankid_endpoint', 'https://appapi2.test.bankid.com/rp/v5.1/');
        update_option('wp_bankid_password', 'qwerty123');

        return true;
    }

    public function setup_settings() {
        // Check if user is administator.
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', esc_html__('You do not have permission to access this resource.', 'wp-bankid'), array('status' => 401));
        }

        // Make sure that the submitted values are valid
        if (!in_array($_POST["wplogin"], array('as_alternative', 'hide'))) {
            return new \WP_Error('invalid_wplogin', esc_html__('Invalid value for wplogin.', 'wp-bankid'), array('status' => 401));
        }
        if (!in_array($_POST["registration"], array('yes', 'no'))) {
            return new \WP_Error('invalid_wplogin', esc_html__('Invalid value for registration.', 'wp-bankid'), array('status' => 401));
        }

        // Update the WP options.
        update_option('wp_bankid_wplogin', $_POST["wplogin"]);
        update_option('wp_bankid_registration', $_POST["registration"]);
        return true;
    }
}