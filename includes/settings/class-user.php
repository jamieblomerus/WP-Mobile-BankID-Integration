<?php
namespace Mobile_BankID_Integration\Settings;
use Personnummer\Personnummer;

new UserSettings;

class UserSettings {
    function __construct() {
        add_action('show_user_profile', array($this, 'showPersonalNumber'));
        add_action('edit_user_profile', array($this, 'showPersonalNumber'));
        add_action('personal_options_update', array($this, 'savePersonalNumber'));
        add_action('edit_user_profile_update', array($this, 'savePersonalNumber'));
    }
    public function showPersonalNumber($user) {
        ?>
        <h3><?php esc_html_e('BankID Authentication', 'mobile-bankid-integration') ?></h3>
        <table class="form-table">
        <tr>
        <th><label for="personal_number"><?php esc_html_e('Personal number (12 digits, no hyphen)', 'mobile-bankid-integration') ?></label></th>
        <td>
        <input type="text" name="personal_number" id="personal_number" <?php if (!current_user_can('administrator')) { echo "disabled"; } ?> value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_bankid_integration_personal_number', true)) ?>" placeholder="<?php /* translators: Placeholder personal number. */ esc_attr_e('YYYYMMDDXXXX', 'mobile-bankid-integration') ?>" class="regular-text" />
        </td>
        </tr>
        </table>
        <?php
    }
    public function savePersonalNumber($user_id) {
        if (current_user_can('administrator')) {
            // Check if personal number is valid and save it if it is.
            if (preg_match('/^[0-9]{12}$/', $_POST['personal_number']) && Personnummer::valid($_POST['personal_number'])) {
                // Check if user with this personal number already exists.
                $check = get_users(array(
                    'meta_key' => 'mobile_bankid_integration_personal_number',
                    'meta_value' => $_POST['personal_number'],
                    'number' => 1,
                    'count_total' => false,
                ));
                if ($check && $check[0]->ID != $user_id) {
                    add_action('user_profile_update_errors', array($this, 'personalNumberUpdateErrorAlreadyExists'), 10, 3);
                    return;
                }

                update_user_meta($user_id, 'mobile_bankid_integration_personal_number', $_POST['personal_number']);
            } elseif (strlen($_POST['personal_number']) < 1) {
                try {
                    delete_user_meta($user_id, 'personal_number');
                } catch (\Exception $e) {
                    // Do nothing.
                }
            } else {
                add_action('user_profile_update_errors', array($this, 'personalNumberUpdateErrorInvalid'), 10, 3);
                return;
            }
        }
    }
    public function personalNumberUpdateErrorAlreadyExists($errors, $update, $user) {
        $errors->add('personal_number', esc_html__('User with this personal number already exists.', 'mobile-bankid-integration'));
    }
    public function personalNumberUpdateErrorInvalid($errors, $update, $user) {
        $errors->add('personal_number', esc_html__('Personal number is not valid.', 'mobile-bankid-integration'));
    }
}