<?php
namespace Webbstart\WP_BankID\Settings;

new Setup;

class Setup {
    // Setup page on activation.
    public function __construct() {
        add_action('admin_menu', array($this, 'wizard'));
    }

    public function wizard() {
        // Register the wizard page as submenu
        add_submenu_page(
            'options-general.php',
            esc_html__('WP BankID Setup', 'wp-bankid'),
            esc_html__('WP BankID Setup', 'wp-bankid'),
            'manage_options',
            'wp-bankid-setup',
            array($this, 'wizard_page')
        );
    }

    public function wizard_page() {
        // Check if the user is allowed to access this page.
        if (!current_user_can('manage_options')) {
            return;
        }
        // Register styles and scripts.
        $this->register_scripts();
        // Load the setup page.
        require_once WP_BANKID_PLUGIN_DIR . 'includes/settings/views/setup.php';
    }

    public function register_scripts() {
        // Register styles.
        wp_register_style('wp-bankid-setup', WP_BANKID_PLUGIN_URL . 'assets/css/setup.css', array(), WP_BANKID_VERSION);
        wp_enqueue_style('wp-bankid-setup');
        // Register scripts.
        wp_register_script('wp-bankid-setup', WP_BANKID_PLUGIN_URL . 'assets/js/setup.js', array(), WP_BANKID_VERSION, true);
        wp_enqueue_script('wp-bankid-setup');
        wp_localize_script('wp-bankid-setup', 'wp_bankid_setup_localization', array(
            'confirmation_abort_text' => esc_html__('Press "Abort" below to abort this operation.', 'wp-bankid'),
            'testenv_confirmation_text' => esc_html__('Are you sure you want to auto-configure the plugin for the test enviroment? This is not considered safe as test BankIDs are open for registration by anyone, without any kind of identification. DO NOT USE TEST ENVIRONMENT ON A PRODUCTION SITE.', 'wp-bankid'),
            'testenv_autoconfig_failed' => __('Autoconfigure test environment failed. Please try again or configure manually.', 'wp-bankid'),
            'configuration_failed' => __('Configuration failed. The following error was reported by the server: ', 'wp-bankid'),
            // Manual configuration form validation.
            'endpoint_required' => __('API Endpoint is required.', 'wp-bankid'),
            'certificate_required' => __('Path to .p12 certificate is required.', 'wp-bankid'),
            'password_required' => __('Password for the .p12 certificate is required.', 'wp-bankid'),
        ));
        wp_add_inline_script('wp-bankid-setup', 'var wp_bankid_rest_api = "' . rest_url('wp-bankid/v1/settings') . '"; var wp_bankid_rest_api_nonce = "'. wp_create_nonce('wp_rest'). '";', 'before');
    }
}