<?php
namespace Mobile_BankID_Integration\Privacy;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

new Data_Export();

/**
 * This class handles the data export.
 */
class Data_Export {

    /**
     * Class constructor that adds the data export.
     */
    public function __construct() {
        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'add_exporter' ) );
    }

    /**
     * Add data exporter for Mobile BankID Integration.
     *
     * @param array $exporters Array of exporters.
     * @return array
     */
    public function add_exporter( $exporters ) {
        $exporters[] = array(
            'exporter_friendly_name' => __( 'Mobile BankID Integration', 'mobile-bankid-integration' ),
            'callback'               => array( $this, 'exporter' ),
        );

        return $exporters;
    }

    /**
     * Export personal data for Mobile BankID Integration.
     * 
     * Export saved personal identity number.
     *
     * @param string $email_address Email address.
     * @return array
     */
    public function exporter( $email_address ) {
        $data_to_export = array();

        // Get user ID.
        $user = get_user_by( 'email', $email_address );

        if ( ! $user ) {
            return array(
                'data' => $data_to_export,
                'done' => true,
            );
        }

        // Get personal identity number.
        $personal_number = get_user_meta( $user->ID, 'mobile_bankid_integration_personal_number', true );

        $data_to_export[] = array(
            'group_id'    => 'mobile_bankid_integration',
            'group_label' => __( 'Mobile BankID Integration', 'mobile-bankid-integration' ),
            'item_id'     => 'personal_number',
            'data'        => array(
                array(
                    'name'  => __( 'Personal identity number', 'mobile-bankid-integration' ),
                    'value' => ( $personal_number ) ? $personal_number : __( 'No personal identity number found', 'mobile-bankid-integration' ),
                ),
            )
        );

        return array(
            'data' => $data_to_export,
            'done' => true,
        );
    }
}