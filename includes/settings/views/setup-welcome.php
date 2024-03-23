<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<h1><?php esc_html_e( 'Mobile BankID Integration ⎯ Setup', 'mobile-bankid-integration' ); ?></h1>
<p><?php esc_html_e( 'This is a plugin that allows you to use BankID to login to your WordPress site. But it also provides all these cool features:', 'mobile-bankid-integration' ); ?></p>
<ul>
	<li><?php esc_html_e( 'Support for localization', 'mobile-bankid-integration' ); ?></li>
	<li><?php esc_html_e( 'Woocommerce integration', 'mobile-bankid-integration' ); ?></li>
	<li><?php esc_html_e( 'Perform age checks (Woocommerce)', 'mobile-bankid-integration' ); ?></li>
	<li><?php esc_html_e( 'Very developer and editor friendly', 'mobile-bankid-integration' ); ?></li>
</ul>
<p><strong>Let's start the setup process.</strong></p>
<button class="button button-primary" onclick="nextStep()" id="mobile-bankid-integration-setup"><?php esc_html_e( 'Next', 'mobile-bankid-integration' ); ?></button>