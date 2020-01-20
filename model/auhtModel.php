<?php
session_start();
// Generate a hashed parameter based on current time for CSRF security
$state = md5(time());

if (!$_GET['action']) {
    $params = array(
    'aud' => 'https://e2emerchant.itsme.be/oidc/authorization',
    'scope' => 'openid service:gw6X9X23Tk profile email',
    'redirect_uri' => 'http://localhost/pdomvc/index.php/music',
    'response_type' => 'code',
    'client_id' => 'MONKYPROOF',
    'acr_values' => 'tag:sixdots.be,2016-06:acr_advanced',
    'iss' => 'MONKYPROOF',
    'state' => $state
    );

    $_SESSION['openid']=$params['state'];
    $str_params = '';
    foreach ($params as $key=>$value) {
        $str_params .= $key . "=" . urlencode($value) . "&";
    }
} elseif (empty($_GET['state']) || (isset($_SESSION['openid']) && $_GET['state'] !== $_SESSION['openid'])) {
    // If the "state" var is present in the $_GET, let's validate it
    if (isset($_SESSION['openid'])) {
        unset($_SESSION['openid']);
    }
    
    exit('Invalid state');
} elseif (isset($_GET['code']) && !empty($_GET['code'])) {
    // If the auth "code" is present in the $_GET
    // let's exchange it for the access token
    $params = array(
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => 'https://localhost/pdomvc/index.php/music',
    'client_assertion' => [
        'iss' => 'MONKYPROOF',
        'sub' => 'MONKYPROOF',
        'aud' => 'https://e2emerchant.itsme.be/oidc/token',
        'jti' => '6f05ad622a3d32a5a81aee5d73a5826adb8cbf65',
        'exp' => '3600'
    ],
    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
    );

    $str_params = '';
    foreach ($params as $key=>$value) {
        $str_params .= $key . "=" . urlencode($value) . "&";
    }

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://e2emerchant.itsme.be/oidc/token",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $str_params
    ));

    $curl_response = curl_exec($curl);
    $curl_error = curl_error($curl);

    curl_close($curl);

    if ($curl_error) {
        echo "Error in the CURL response:" . $curl_error;
    } else {
        $arr_json_data = json_decode($curl_response);

        if (isset($arr_json_data->access_token)) {
            $access_token = $arr_json_data->access_token;
            $curl = curl_init();

            curl_setopt_array($curl, array(
        CURLOPT_URL => "https://e2emerchant.itsme.be/oidc/.well-known/openid-configuration",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$access_token}"
        )
        ));

            $curl_response = curl_exec($curl);
            $curl_error = curl_error($curl);

            curl_close($curl);

            if ($curl_error) {
                echo "Error in the CURL response from It's Me API:" . $curl_error;
            } else {
                echo "It's Me Response:" . $curl_response;
            }
        } else {
            echo 'Invalid response, no access token was found.';
        }
    }
}