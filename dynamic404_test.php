<?php
/**
 * The following script should be modified to represent your own URL ($url).
 * Next, upload this file to a hosting environment and run it.
 * The output of this file should be the same as the output of the URL
 * in your browser. If it is not, it proves that there is a
 * misconfiguration of the webserver, to which the hosting provider
 * needs to respond.
 */

// Edit the following URL
$url = '';

// Do not the following code 
$client = curl_init($url);  
curl_setopt($client, CURLOPT_BODY, 1);
curl_setopt($client, CURLOPT_HEADER, 0);
curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($client);

if (!empty($response)) {
    echo $response;
    exit;
}

$client = curl_init($url);  
curl_setopt($client, CURLOPT_BODY, 0);
curl_setopt($client, CURLOPT_HEADER, 1);
curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($client);
echo $response;
