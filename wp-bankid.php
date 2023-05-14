<?php
/*
Plugin Name: WP BankID by Webbstart
Description: This plugin allows you to integrate BankID with your WordPress site.
Version: Indev
Author: Webbstart
Author URI: https://webbstart.nu/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define constants
define( 'WP_BANKID_VERSION', 'Indev' );
define( 'WP_BANKID_PLUGIN_FILE', __FILE__ );
define( 'WP_BANKID_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_BANKID_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if (!class_exists("wp_bankid")) {
    class wp_bankid
    {
        public function __construct()
        {
            // Composer autoload
            require_once WP_BANKID_PLUGIN_DIR . 'vendor/autoload.php';

            // Load plugin files
            $this->load_plugin_files();

            // Add link in plugin list
            add_filter('plugin_action_links_' . plugin_basename(WP_BANKID_PLUGIN_FILE), [$this, 'plugin_list_link']);
        }

        private function load_plugin_files()
        {
            require_once WP_BANKID_PLUGIN_DIR . 'includes/class-core.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/class-activation.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/settings/class-setup.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/settings/class-api.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/settings/class-user.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/wp-login/class-api.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/wp-login/class-login.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/admin/class-admin.php';
        }

        public function plugin_list_link($links) {
            $setup_link = '<a href="admin.php?page=wp-bankid">'.esc_html__("Settings", "wp-bankid").'</a>';
            array_unshift($links, $setup_link);
            return $links;
        }
    }
    new wp_bankid;
}