<?php

require_once('MxitAPI.php');

try {
    $key = 'f00df00df00df00df00df00df00df00d';
    $secret = 'f00df00df00df00df00df00df00df00d';

    $api = new MxitAPI($key, $secret);

    if (isset($_GET) && isset($_GET['code'])) {
        $api->get_user_token($_GET['code'], 'http://www.example.com');

        // Get Contacts
        $contacts = $api->get_contact_list('@Apps', 0, 2);
        print_r($contacts);

        // Update Status
        echo "Status update ";
        $api->set_status('insert status here...');
        echo ($api->http_status == 200) ? 'successful' : 'failed';
        echo "<br />";

        // Set Avatar
        echo "Set avatar ";
        $avatar = file_get_contents('/Users/ashley/Pictures/goblin.jpg');
        $avatar = base64_encode($avatar);
        $api->set_avatar($avatar);
        echo ($api->http_status == 200) ? 'successful' : 'failed';
        echo "<br />";

    } elseif (isset($_GET) && isset($_GET['error']) && isset($_GET['error_description'])) {
        echo "<h2>There was an error requesting access from the API</h2>";
        echo '<strong>Error:</strong> '. $_GET['error'] .'<br />';
        echo '<strong>Error Description:</string> '. $_GET['error_description'] .'<br />';
    } else {
        $api->request_access('http://www.example.com', 'graph/read status/write avatar/write');
    }

} catch (Exception $e) {
    echo $e->getMessage();
}

?>
