<?php
namespace Webbstart\WP_BankID\Integrations\WooCommerce;
use \Webbstart\WP_BankID\Core;

new Settings;

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
                'desc' => __( 'Let customers login using Mobile BankID.', 'wp-bankid' ),
                'id' => 'wp_bankid_woocommerce_login-',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'default' => 'no'
            );
            /* Age check */
            $settings_wp_bankid[] = array(
                'name' => __( 'Age check', 'wp-bankid' ),
                'desc' => __( 'Require <strong>all</strong> customers to prove their age.', 'wp-bankid' ),
                'id' => 'wp_bankid_woocommerce_age_check',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'default' => 'no'
            );
            /* Age check age */
            $settings_wp_bankid[] = array(
                'name' => __( 'Age check age', 'wp-bankid' ),
                'desc' => __( 'Require <strong>all</strong> customers to be at least this old.', 'wp-bankid' ),
                'id' => 'wp_bankid_woocommerce_age_required',
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