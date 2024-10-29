<?php
namespace Mobile_BankID_Integration\WP_Login;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

new Login();

/**
 * This class provides the login button on the login page.
 */
class Login {

	/**
	 * Class constructor that adds the login button to the login page if the plugin is configured to do so.
	 */
	public function __construct() {
		if ( get_option( 'mobile_bankid_integration_wplogin' ) === 'as_alternative' && ( get_option( 'mobile_bankid_integration_certificate' ) && get_option( 'mobile_bankid_integration_password' ) && get_option( 'mobile_bankid_integration_env' ) ) ) {
			add_action( 'login_form', array( $this, 'login_button' ), 40 );
			add_action( 'login_footer', array( $this, 'login_container' ) );
			add_action(
				'login_footer',
				function () {
					$this->terms();
				},
				40
			);
		}
	}

	/**
	 * Add login button to login page.
	 *
	 * @param string $redirect URL to redirect to after login.
	 * @return void
	 */
	public function login_button( $redirect = '' ) {
		if ( empty( $redirect ) ) {
			$redirect = '/wp-admin/';
		}
		?>
		<p>
			<button id="bankid-login-button" class="button wp-element-button"><?php esc_html_e( 'Login with BankID', 'mobile-bankid-integration' ) ?></button>
		</p>
		<?php
		$this->load_scripts( $redirect );
	}

	/**
	 * Add login container to login page.
	 *
	 * @return void
	 */
	public function login_container($dom_element = 'form', $class = array()) {
		if ( ! in_array( $dom_element, array( 'form', 'div', 'section' ), true ) ) {
			$dom_element = 'form';
		}
		?>
		<<?php echo esc_attr( $dom_element ); ?> id="bankid-login-container" class="<?php echo esc_attr( implode( ' ', $class ) ); ?>" style="display: none;">
			<h2 id="bankid-login-h2"><?php esc_html_e( 'Login with BankID', 'mobile-bankid-integration' ) ?></h2>
			<p id="bankid-status"><?php esc_html_e( 'Scan the QR code with your Mobile BankID app.', 'mobile-bankid-integration' ) ?></p>
			<div id="bankid-qr-code-container" role="button" aria-label="<?php esc_attr_e( 'Enlarge the QR code', 'mobile-bankid-integration' ) ?>">
				<img id="bankid-qr-code" src="" alt="<?php esc_attr_e( 'QR code', 'mobile-bankid-integration' ) ?>">
				<div id="bankid-qr-code-loading" aria-hidden="true">
					<div class="spinner"></div>
				</div>
				<div id="bankid-qr-code-timeleft" aria-hidden="true"></div>
			</div>
			<div class="accordion screen-reader-accordion" role="region">
				<button class="accordion-button" aria-expanded="false" aria-controls="bankid-screen-reader-help"><?php esc_html_e( 'If you use a screen reader', 'mobile-bankid-integration' ) ?><span class="icon" aria-hidden="true"></span></button>
				<div id="bankid-screen-reader-help" class="accordion-content">
					<p><?php esc_html_e( 'The most common problem is that the QR code doesn\'t fit on the screen. Please try to:', 'mobile-bankid-integration' ) ?></p>
					<ul>
						<li><?php esc_html_e( 'Ensure that the screen is switched on and Screen Curtain or similar functions are switched off.', 'mobile-bankid-integration' ) ?></li>
						<li><?php esc_html_e( 'Zoom out in your browser by pressing Ctrl or Cmd-0.', 'mobile-bankid-integration' ) ?></li>
						<li><?php esc_html_e( 'Zoom out with magnification tools such as ZoomText.', 'mobile-bankid-integration' ) ?></li>
						<li><?php esc_html_e( 'Ensure the browser window is maximized.', 'mobile-bankid-integration' ) ?></li>
						<li><?php esc_html_e( 'Hold your phone in portrait mode at an arm\'s lengths distance from the screen when you scan the QR code.', 'mobile-bankid-integration' ) ?></li>
					</ul>
					<p><?php esc_html_e( 'You can also click on the QR code above for it to be displayed bigger.', 'mobile-bankid-integration' ) ?></p>
				</div>
			</div>
			<a href="#" id="cancel_bankid" class="button wp-element-button"><?php esc_html_e( 'Cancel', 'mobile-bankid-integration' ) ?></a>
			<a target="_blank" id="open_bankid" href="#" class="button wp-element-button" style="margin-left: 5px;"><?php esc_html_e( 'Start the BankID app', 'mobile-bankid-integration' ) ?></a>
		</<?php echo esc_attr( $dom_element ); ?>>
		<?php
	}

	/**
	 * Add terms to login page.
	 *
	 * @param float $font_size Font size in rem.
	 * @return void
	 */
	public function terms( float $font_size = 0.7 ) {
		if ( get_option( 'mobile_bankid_integration_terms' ) === '' ) {
			return;
		}
		?>
		<p id="bankid-terms" style="font-size: <?php echo( esc_html( strval( $font_size ) ) ); ?>rem;">
			<?php
			echo wp_kses(
				get_option( 'mobile_bankid_integration_terms', esc_html__( 'By logging in using Mobile BankID you agree to our Terms of Service and Privacy Policy.', 'mobile-bankid-integration' ) ),
				array(
					'a'      => array(
						'href'   => array(),
						'title'  => array(),
						'target' => array(),
					),
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
					'i'      => array(),
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Load scripts for login page.
	 *
	 * @param string $redirect URL to redirect to after login.
	 * @return void
	 */
	public function load_scripts( string $redirect ) {
		// If the messages are updated, remember to update it in woocommerce.php as well.
		wp_register_script( 'mobile-bankid-integration-login', MOBILE_BANKID_INTEGRATION_PLUGIN_URL . 'assets/js/login.js', array( 'jquery' ), MOBILE_BANKID_INTEGRATION_VERSION, true );
		wp_enqueue_script( 'mobile-bankid-integration-login' );
		wp_enqueue_script( 'jquery' );
		wp_localize_script(
			'mobile-bankid-integration-login',
			'mobile_bankid_integration_login_localization',
			array(
				'qr_click_to_enlarge'     => esc_attr__( 'Enlarge the QR code', 'mobile-bankid-integration' ),
				'qr_click_to_shrink'      => esc_attr__( 'Shrink the QR code back to normal size', 'mobile-bankid-integration' ),
				'qr_instructions'         => esc_html__( 'Scan the QR code with your Mobile BankID app.', 'mobile-bankid-integration' ),
				'status_expired'          => esc_html__( 'BankID identification session has expired. Please try again.', 'mobile-bankid-integration' ),
				'status_complete'         => esc_html__( 'BankID identification completed. Redirecting...', 'mobile-bankid-integration' ),
				'status_complete_no_user' => esc_html__( 'BankID identification completed, but no user was found. Please try again.', 'mobile-bankid-integration' ),
				'status_failed'           => esc_html__( 'BankID identification failed. Please try again.', 'mobile-bankid-integration' ),
				'something_went_wrong'    => esc_html__( 'Something went wrong. Please try again.', 'mobile-bankid-integration' ),

				// BankID Hint Code with translation note.
				'hintcode_userCancel'     => esc_html__( 'Action cancelled.', 'mobile-bankid-integration' ),
				'hintcode_userSign'       => esc_html__( 'Enter your security code in the BankID app and select Identify.', 'mobile-bankid-integration' ),
				'hintcode_startFailed'    => esc_html__( 'Failed to scan the QR code.', 'mobile-bankid-integration' ),
				'hintcode_certificateErr' => esc_html__( 'The BankID you are trying to use is revoked or too old. Please use another BankID or order a new one from your internet bank.', 'mobile-bankid-integration' ),
			)
		);
		wp_add_inline_script( 'mobile-bankid-integration-login', 'var mobile_bankid_integration_rest_api = "' . rest_url( 'mobile-bankid-integration/v1/login' ) . '"; var mobile_bankid_integration_redirect_url = "' . $redirect . '";', 'before' );

		wp_enqueue_style( 'mobile-bankid-integration-login', MOBILE_BANKID_INTEGRATION_PLUGIN_URL . 'assets/css/login.css', array(), MOBILE_BANKID_INTEGRATION_VERSION );
	}
}