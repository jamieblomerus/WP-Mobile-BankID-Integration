<?php
namespace Mobile_BankID_Integration\WP_Login;

use \Mobile_BankID_Integration\Core;
use \chillerlan\QRCode\QRCode;

new API;

class API {
    public function __construct() {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes() {
        register_rest_route('mobile-bankid-integration/v1/login', '/identify', array(
            'methods' => 'POST',
            'callback' => [$this, 'identify'],
            'permission_callback' => '__return_true'
        ));
        register_rest_route('mobile-bankid-integration/v1/login', '/status', array(
            'methods' => 'GET',
            'callback' => [$this, 'status'],
            'permission_callback' => '__return_true'
        ));
    }

    public function identify() {
        $instance = Core::$instance;
        $response = $instance->identify();
        return $response;
    }
    public function status() {
        $instance = Core::$instance;

        if (!isset($_GET['orderRef'])) {
            return new \WP_Error('no_orderRef', 'No orderRef provided.', array('status' => 400));
        }

        $orderRef = $_GET['orderRef'];
        $db_row = $instance->getAuthResponseFromDB($orderRef);
        if (!isset($db_row)) {
            return new \WP_Error('no_orderRef', 'No orderRef found in DB.', array('status' => 400));
        }
        $auth_response = $db_row['response'];
        $time = time();
        $time_since_auth = $time - $db_row['time_created'];

        $status = $instance->get_bankid_service()->collectResponse($auth_response['orderRef']);

        if ($status->status == "failed" ) {
            $instance->deleteAuthResponseFromDB($orderRef);
            $return = [
                "qr" => null,
                "orderRef" => $orderRef,
                "time_since_auth" => $time_since_auth,
                "status" => "failed",
                "hintCode" => $status->hintCode
            ];
            return $return;
        }

        if ($status->status == "complete") {
            $instance->deleteAuthResponseFromDB($orderRef);
            if ($this->signInAsUserFromBankID($status->completionData->user->personalNumber, $status->completionData->user->givenName, $status->completionData->user->surname) == false) {
                return [
                    "qr" => null,
                    "orderRef" => $orderRef,
                    "time_since_auth" => $time_since_auth,
                    "status" => "complete_no_user",
                ];
            }
            return [
                "qr" => null,
                "orderRef" => $orderRef,
                "time_since_auth" => $time_since_auth,
                "status" => "complete",
            ];
        }

        $qr = new QRCode;
        $qrCode = $qr->render("bankid.".$auth_response['qrStartToken'].".".$time_since_auth.".".hash_hmac('sha256', $time_since_auth, $auth_response['qrStartSecret']));
        return [
            "qr" => $qrCode,
            "orderRef" => $orderRef,
            "time_since_auth" => $time_since_auth,
            "status" => $status->status,
            "hintCode" => $status->hintCode ?? "",
        ];
    }

    private function signInAsUserFromBankID($personal_number, $fname, $lname) {
        // Get user by personal number from DB.
        $user_id = Core::$instance->getUserIdFromPersonalNumber($personal_number);

        $user = get_user_by('id', $user_id);
        if (!$user) {
            if (get_option('mobile_bankid_integration_registration') != "yes") {
                return false;
            }

            // Create user.
            $user_id = wp_create_user($this->randomUsername(), wp_generate_password());
            $user = get_user_by('id', $user_id);
            // Set user name
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $fname." ".$lname,
                'first_name' => $fname,
                'last_name' => $lname,
            ));

            // Set user personal number.
            Core::$instance->setPersonalNumberForUser($user_id, $personal_number);
        } else {
            $user_id = $user->ID;
        }
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        Core::$instance->createAuthCookie($user_id);
        do_action('wp_login', $personal_number, $user);
        return $user;
    }

    private function randomUsername(){
        $user_exists = 1;
        do {
           $rnd_str = sprintf("%06d", mt_rand(1, 999999));
           $user_exists = username_exists( "user_" . $rnd_str );
       } while( $user_exists > 0 );
       return "user_" . $rnd_str;
    }
}