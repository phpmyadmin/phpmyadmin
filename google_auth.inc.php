<?php

require_once 'vendor/autoload.php';
function get_google_client()
{
    $client = new Google_Client();
    $client->setApplicationName('CodeChef Secret Service');
    $client->setScopes(Google_Service_PeopleService::USERINFO_EMAIL);
    try {
        $client->setAuthConfig('google_oauth_credentials.json');
    } catch (\Google\Exception $e) {
        echo "Failed to get Google Client";
        echo $e->getMessage();
        exit();
    }
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Refresh the token if possible, else fetch a new one.
    if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    }

    return $client;
}

function show_google_oauth_login_page($client)
{
    header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
}

// Get the API client and construct the service object.
$client = get_google_client();

if (isset($_REQUEST['logout'])) {
    unset($_SESSION['access_token']);
    $client->revokeToken();
    show_google_oauth_login_page($client);
}

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        echo "Failed to authorize";
        exit();
    }
    $client->setAccessToken($token['access_token']);
    $_SESSION['access_token'] = $client->getAccessToken();
} elseif (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
    $_SESSION['access_token'] = $client->getAccessToken();

} else {
    show_google_oauth_login_page($client);
}





