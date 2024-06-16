<?php
namespace Mobile_BankID_Integration\Settings;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Personnummer\Personnummer;

new UserSettings();

/**
 * This class handles the user settings.
 */
class UserSettings {
	/**
	 * Class constructor that adds the user settings.
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'show_personal_number' ) );
		add_action( 'edit_user_profile', array( $this, 'show_personal_number' ) );
		add_action( 'personal_options_update', array( $this, 'save_personal_number' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_personal_number' ) );
	}

	/**
	 * Show personal identity number field.
	 *
	 * @param object $user User object.
	 * @return void
	 */
	public function show_personal_number( $user ) {
		?>
		<h3><?php esc_html_e( 'BankID Authentication', 'mobile-bankid-integration' ); ?></h3>
		<table class="form-table">
		<tr>
		<th><label for="personal_number"><?php esc_html_e( 'Personal identity number (12 digits, no hyphen)', 'mobile-bankid-integration' ); ?></label></th>
		<td>
		<input type="text" name="personal_number" id="personal_number" 
		<?php
		if ( ! current_user_can( 'edit_users' ) ) {
			echo 'disabled'; }
		?>
		value="<?php echo esc_attr( get_user_meta( $user->ID, 'mobile_bankid_integration_personal_number', true ) ); ?>" placeholder="<?php /* translators: Placeholder personal identity number. */ esc_attr_e( 'YYYYMMDDXXXX', 'mobile-bankid-integration' ); ?>" class="regular-text" />
		</td>
		</tr>
		</table>
		<?php
	}

	/**
	 * Save personal identity number field.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_personal_number( $user_id ) {
		// Get param.
		$personal_number = isset( $_POST['personal_number'] ) ? $_POST['personal_number'] : ''; // phpcs:ignore

		if ( current_user_can( 'edit_users' ) ) {
			// Check if personal identity number is valid and save it if it is.
			if ( preg_match( '/^[0-9]{12}$/', $personal_number ) && Personnummer::valid( $personal_number ) ) {
				// Check if user with this personal identity number already exists.
				$check = get_users(
					array(
						'meta_key'    => 'mobile_bankid_integration_personal_number',
						'meta_value'  => $personal_number,
						'number'      => 1,
						'count_total' => false,
					)
				);
				if ( $check && $check[0]->ID !== $user_id ) {
					add_action( 'user_profile_update_errors', array( $this, 'personal_number_update_error_already_exists' ), 10, 3 );
					return;
				}

				update_user_meta( $user_id, 'mobile_bankid_integration_personal_number', $personal_number );
			} elseif ( strlen( $personal_number ) === 0 ) {
				delete_user_meta( $user_id, 'mobile_bankid_integration_personal_number' );
			} else {
				add_action( 'user_profile_update_errors', array( $this, 'personal_number_update_error_invalid' ), 10, 3 );
				return;
			}
		}
	}

	/**
	 * Add error message if personal identity number already exists.
	 *
	 * @param object $errors WP_Error object.
	 * @param bool   $update Whether this is a user update.
	 * @param object $user User object.
	 * @return void
	 */
	public function personal_number_update_error_already_exists( $errors, $update, $user ) {
		$errors->add( 'personal_number', esc_html__( 'User with this personal identity number already exists.', 'mobile-bankid-integration' ) );
	}

	/**
	 * Add error message if personal identity number is invalid.
	 *
	 * @param object $errors WP_Error object.
	 * @param bool   $update Whether this is a user update.
	 * @param object $user User object.
	 * @return void
	 */
	public function personal_number_update_error_invalid( $errors, $update, $user ) {
		$errors->add( 'personal_number', esc_html__( 'Personal identity number is not valid.', 'mobile-bankid-integration' ) );
	}
}