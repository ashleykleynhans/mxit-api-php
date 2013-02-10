PHP Wrapper for the MXit API
============================

This code has been developed and maintained by DigiGoblin.
It is released under the Apache License, Version 2.0

Methods
=======

## MxitAPI::__construct
#### *Description*

Creates an interface to the MXit API using your *Client Id* that was assigned to your application in your dashboard at http://code.mxit.com

#### *Parameters*
*key*: string

#### *Example*

    $key = 'f00df00df00df00df00df00df00df00d';
    $secret = 'f00df00df00df00df00df00df00df00d';

    $api = new MxitAPI($key, $secret);

## request_access
#### *Description*

Requests a user to provide specific MXit permissions to your application.

Once the user grants access, it redirects back to: redirect_uri/cb?code=f00df00df00df00df00df00df00df00d
Eg: http://www.example.com/cb?code=f00df00df00df00df00df00df00df00d

Where the value of code (f00df00df00df00df00df00df00df00d in this example) is used to request the OAuth2 access token, in the get_user_token() method.

NOTE: The access token is only valid for 3600 seconds.

#### *Parameters*
*redirect_uri*: string  
*scope*: string  
*state*: string (optional)

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'profile/public');

## get_user_token
#### *Description*

Gets an OAuth2 token for a user once they have granted permissions to an application.

The request_access() method must be called, before this method is called.
This method processes the code returned from request_access() and obtains an OAuth2 token.

**NOTE: The redirect URI specified in the get_user_token() method must match the redirect URI specified in the request_access() method, otherwise the OAuth2 server will not grant you a valid OAuth2 token.**

#### *Parameters*

*code*: string  
*redirect_uri*: string

#### *Return Value*
*None*

#### *Example*

    $api->get_user_token('f00df00df00df00df00df00df00df00d', 'http://www.example.com/');


## get_app_token
#### *Description*

Gets an OAuth2 token for an application rather than for a user.
This is used for the MXit messaging API - see the send_message() method.

#### *Parameters*

*scope*: string

#### *Return Value*
*None*

#### *Example*

    $api->get_app_token('message/send');

## get_token
#### *Description*

Gets OAuth2 token detail and returns it as an array.
Useful for storing the detail in a Session.

#### *Parameters*

*None*

#### *Return Value*
*ARRAY*

#### *Example*

    $token = $api->get_token();
    $_SESSION['token'] = $token;

## set_token
#### *Description*

Sets OAuth2 token detail from an array.
Useful for setting the OAuth2 token detail from a Session.

#### *Parameters*

*detail*: array

#### *Return Value*
*None*

#### *Example*

    $token = $_SESSION['token'];
    $api->set_token($token);

## get_user_id
#### *Description*

Although some methods in the API allow information to be retrieved using a user's login, other information can only be retrieved using the user's mxitid. This method gets the user's mxitid from their login.

#### *Parameters*
*login*: string

#### *Return Value*
*STRING*

#### *Example*

    $login = 'foobarbaz';
    $api->get_app_token('profile/public');
    $user_id = $api->get_user_id($login);

## get_status
#### *Description*

Gets the status message for a user

#### *Parameters*
*login*: string

#### *Return Value*
*STRING*

#### *Example*

    $login = 'foobarbaz';
    $api->get_app_token('profile/public');
    $status = $api->get_status($login);

## get_avatar
#### *Description*

Gets the avatar image of a user. The *content_type* from the API can be useful for determining the image format.

#### *Parameters*
*login*: string

#### *Return Value*
*BINARY*

#### *Example*

    $login = 'foobarbaz';
    $api->get_app_token('profile/public');
    $avatar = $api->get_avatar($login);
    $content_type = $api->content_type;

## get_display_name
#### *Description*

Gets the display name (nickname) of a user.

#### *Parameters*
*login*: string

#### *Return Value*
*STRING*

#### *Example*

    $login = 'foobarbaz';
    $api->get_app_token('profile/public');
    $display_name = $api->get_display_name($login);

## get_basic_profile
#### *Description*

Gets the basic profile information for the userid

#### *Parameters*
*mxitid*: string

#### *Return Value*
*OBJECT*

#### *Example*

    $login = 'foobarbaz';
    $api->get_app_token('profile/public');
    $user_id = $api->get_user_id($login);
    $profile = $api->get_basic_profile($user_id);

# send_message
#### *Description*

Send a message.

#### *Parameters*
*From*: string  
*To*: string  
*Message*: string  
*Contains Markup*: string

#### *Return Value*
*None*

#### *Example*

    $api->get_app_token('message/send');
    $api->send_message('foobarbaz', 'm11111111111', 'test message', 'true');


## get_full_profile
#### *Description*

Gets the full profile information (including email address and phone number) for the userid in the access token.

#### *Parameters*
*None*

#### *Return Value*
*OBJECT*

#### *Example*

    $api->request_access('http://www.example.com', 'profile/private');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $profile = $api->get_full_profile();

## update_profile
#### *Description*

Updates the profile information for the userid in the access token.

#### *Parameters*
*Data*: array

#### *Return Value*
*OBJECT*

#### *Example*
                  
    $api->request_access('http://www.example.com', 'profile/write');

#### Once MXit redirects to your application

    $data = array('FirstName'          => 'Joe',
                  'LastName'           => 'Soap',
                  'Title'              => 'Mr',
                  'DisplayName'        => 'Zeus',
                  'Gender'             => 0,
                  'RelationshipStatus' => 0,
                  'WhereAmI'           => 'Cape Town',
                  'AboutMe'            => 'Friendly',
                  'Email'              => 'hello@example.com',
                  'MobileNumber'       => '0715555555');

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $profile = $api->update_profile($data);

## add_contact
#### *Description*

Subscribes the userid contained in the access token, to the service or sends an invite to another user. If the contact is a Service, then the service is added and accepted. If the contact is a MXitId, then an invite is sent to the other user.

#### *Parameters*
*None*

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'contact/invite');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->add_contact('hangingman');

## get_contact_list
#### *Description*

Gets the list of contacts for the userid contained in the access token.

#### *Parameters*
*ContactTypes*: string (optional)  
*Skip*: integer (optional)  
*Count*: integer (optional)

#### *Return Value*
*OBJECT*

#### *Example*

    $api->request_access('http://www.example.com', 'graph/read');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $contacts = $api->get_contact_list('@Apps', 0, 2);

## get_friend_suggestions
#### *Description*

Gets a list of suggested friends for the userid contained in the access token.

#### *Parameters*
*None*

#### *Return Value*
*OBJECT*

#### *Example*

    $api->request_access('http://www.example.com', 'graph/read');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $contacts = $api->get_friend_suggestions();

## set_status
#### *Description*

Set the status message for the userid contained in the access token.

#### *Parameters*
*Status Message*

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'status/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->set_status('test status');

## set_avatar
#### *Description*

Upload an avatar image for the userid contained in the access token.

#### *Parameters*
*Image*: binary data  
*Mime Type*: string

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'avatar/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $avatar = file_get_contents('/Users/ashley/Pictures/goblin.jpg');
    $api->set_avatar($avatar, 'image/jpeg');

## delete_avatar
#### *Description*

Delete the avatar image for the userid contained in the access token.

#### *Parameters*
*None*

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'avatar/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->delete_avatar();

## list_gallery_folders
#### *Description*

List gallery folders for the userid contained in the access token.

#### *Parameters*
*None*

#### *Return Value*
*OBJECT*

#### *Example*

    $api->request_access('http://www.example.com', 'content/read');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $folders = $api->list_gallery_folders();

## list_gallery_items
#### *Description*

List the items in a specific gallery folder for the userid contained in the access token.

#### *Parameters*
*Folder*: string  
*Skip*: integer (optional)  
*Count*: integer (optional)

#### *Return Value*
*OBJECT*

#### *Example*

    $api->request_access('http://www.example.com', 'content/read');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $items = $api->list_gallery_items('Default');

## create_gallery_folder
#### *Description*

Create a new gallery folder for the userid contained in the access token.

#### *Parameters*
*Folder*: string

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'content/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->create_gallery_folder('Foo');

# rename_gallery_folder
#### *Description*

Rename a new gallery folder for the userid contained in the access token.

#### *Parameters*
*Source*: string  
*Destination*: string

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'content/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->rename_gallery_folder('Foo', 'Bar');

# delete_gallery_folder
#### *Description*

Delete a new gallery folder for the userid contained in the access token.

#### *Parameters*
*Folder*: string

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'content/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->delete_gallery_folder('Bar');

# download_gallery_image
#### *Description*

Download an image from a gallery folder for the userid contained in the access token.

#### *Parameters*
*FileId*: string

#### *Return Value*
*BINARY*

#### *Example*

    $api->request_access('http://www.example.com', 'content/read');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $image = $api->download_gallery_image('f00df00d-f00d-f00d-f00d-f00df00df00d');
    $content_type = $api->content_type;

# upload_gallery_image
#### *Description*

Upload an image to a gallery folder for the userid contained in the access token.

#### *Parameters*
*Folder*: string  
*File Name*: string  
*Mime Type*: string  
*Image*: binary data

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'content/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $image = file_get_contents('/Users/ashley/Pictures/profile.jpg');
    $api->upload_gallery_image('Default', 'profile.jpg', 'image/jpeg', $image);

# rename_gallery_image
#### *Description*

Rename an image in a gallery folder for the userid contained in the access token.

#### *Parameters*
*File Id*: string  
*Destination*: string

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'content/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->rename_gallery_image('f00df00d-f00d-f00d-f00d-f00df00df00d', 'Renamed Image');

# delete_gallery_image
#### *Description*

Delete an image from a gallery folder for the userid contained in the access token.

#### *Parameters*
*File Id*: string

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'content/write');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $api->delete_gallery_image('f00df00d-f00d-f00d-f00d-f00df00df00d');
    
# send_file
#### *Description*

Send a file to a user

#### *Parameters*
*User Id*: string  
*File Name*: string  
*Mime Type*: string  
*Data*: binary data

#### *Return Value*
*None*

#### *Example*

    $api->request_access('http://www.example.com', 'content/send');

#### Once MXit redirects to your application

    $api->get_user_token($_GET['code'], 'http://www.example.com');
    $image = file_get_contents('/Users/ashley/Pictures/profile.jpg');
    $api->send_file('m123456789, 'profile.jpg', 'image/jpeg', $image);
    
