<?php
namespace Webbstart\WP_BankID;

new Activation;

class Activation {
    function __construct() {
        register_activation_hook(WP_BANKID_PLUGIN_FILE, array($this, 'activation'));
        register_deactivation_hook(WP_BANKID_PLUGIN_FILE, array($this, 'deactivation'));
    }

    public function activation() {
        $this->checkrequirements();
        // Create DB table for storing auth responses.
        global $wpdb;
        $table_name = $wpdb->prefix . 'wp_bankid_auth_responses';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time_created bigint NOT NULL,
            response text NOT NULL,
            orderRef text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivation() {
        // Delete DB table.
        global $wpdb;
        $table_name = $wpdb->prefix . 'wp_bankid_auth_responses';
        $sql = "DROP TABLE $table_name";
        $wpdb->query($sql);

    }

    private function checkrequirements() {
        // Check if running on Windows.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            wp_die(esc_html__('Due to bugs and limitations in our dependencies, this plugin does not work on Windows. Please try again on a Linux server.'));
        }
        // Check if PHP version is 7.4 or higher.
        if (version_compare(PHP_VERSION, '7.4.0') < 0) {
            wp_die(esc_html__('PHP version 7.4 or higher is required for this plugin to work.'));
        }
        // Check if curl is installed.
        if (!function_exists('curl_version')) {
            wp_die(esc_html__('cURL is required for this plugin to work.'));
        }
    }
}