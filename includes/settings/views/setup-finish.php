<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<h1><?php esc_html_e( 'Setup is complete', 'mobile-bankid-integration' ); ?></h1>
<p><?php esc_html_e( 'Mobile BankID Integration is now setup and ready to use. You can now login to your WordPress site using BankID as soon as you have added your personal identity number to your account.', 'mobile-bankid-integration' ); ?></p>

<a href="<?php echo esc_url( admin_url( 'admin.php?page=mobile-bankid-integration' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to settings', 'mobile-bankid-integration' ); ?></a>