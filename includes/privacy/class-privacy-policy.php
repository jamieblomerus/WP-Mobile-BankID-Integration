<?php
namespace Mobile_BankID_Integration\Privacy;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

new Privacy_Policy();

/**
 * This class handles the privacy policy suggestions.
 */
class Privacy_Policy {

    /**
     * Class constructor that adds the privacy policy suggestions.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'privacy_policy' ) );
    }

    /**
     * Add privacy policy suggestions that informs the user about the data that is collected from BankID and shared with BankID.
     *
     * @return void
     */
    public function privacy_policy() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }

        $content = '<h2>' . __( 'Mobile BankID Integration', 'mobile-bankid-integration' ) . '</h2>';
        $content .= '<p>' . __( 'When you login, authenticate or sign using Mobile BankID, we collect your personal data such as your IP address, device information and sometimes your personal identity number. This data is shared with BankID for authentication purposes.', 'mobile-bankid-integration' ) . '</p>';
        $content .= '<p>' . __( 'We may also store data from your Mobile BankID and store it in our database for future reference. This includes, but is not limited to, your personal identity number, name, ip address, unique hardware identifier, issue date of your BankID and related information.', 'mobile-bankid-integration' ) . '</p>';
        $content .= '<p>' . __( 'The purpose of this data is to provide you with a secure and easy way to authenticate yourself on our website.', 'mobile-bankid-integration' ) . '</p>';

        wp_add_privacy_policy_content(
            __( 'Mobile BankID Integration', 'mobile-bankid-integration' ),
            wp_kses_post( wpautop( $content, false ) )
        );
    }
}