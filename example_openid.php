<?php

require_once('MxitAPI.php');

try {
    $key = 'f00df00df00df00df00df00df00df00d';
    $secret = 'f00df00df00df00df00df00df00df00d';

    $api = new MxitAPI($key, $secret);

    if (isset($_GET) && count($_GET)) {
        if ($api->authentication_status() === TRUE) {
            $api->get_user_token($_GET['code'], 'http://www.example.com');
            $user_id = $api->validate_token();

            if ($user_id === FALSE) {
              echo 'ERROR: Unable to authenticate user';
            } else {
              echo '<pre>';
              $api->get_app_token('profile/public');
              $profile = $api->get_basic_profile($user_id);
              var_dump($profile);
              echo '</pre>';
            }

        } else {
            echo 'Authentication failed<br />';
            echo 'Error: '. $_GET['error'] .'<br />';
            echo 'Error Description: '. $_GET['error_description'] .'<br />';
        }

    } else {
        $api->authenticate('http://www.example.com');
    }

} catch (Exception $e) {
    echo $e->getMessage();
}

?>
