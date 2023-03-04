<?php
/*
Plugin Name: WP BankID by Webbstart
Description: This plugin allows you to integrate BankID with your WordPress site.
Version: private-alpha1
Author: Webbstart
Author URI: https://webbstart.nu/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define constants
define( 'WP_BANKID_VERSION', 'private-alpha1' );
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
        }

        public function load_plugin_files()
        {
            require_once WP_BANKID_PLUGIN_DIR . 'includes/class-core.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/class-activation.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/settings/class-setup.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/settings/class-api.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/settings/class-user.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/wp-login/class-api.php';
            require_once WP_BANKID_PLUGIN_DIR . 'includes/wp-login/class-login.php';
        }
    }
    new wp_bankid;
}