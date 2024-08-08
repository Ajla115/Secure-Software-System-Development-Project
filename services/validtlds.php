<?php

// $str = "ajla.korman@stu.ibu.edu.ba";
// $str_array = explode(".", $str);
// $tld = end($str_array);


$ch = curl_init("https://data.iana.org/TLD/tlds-alpha-by-domain.txt");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

curl_close($ch);
if ($response === false) {
    // Handle error; the request failed
    exit('Could not retrieve data from the API.');
}

$tld_array = explode("\n", $response);
//da preskocim onaj prvi element sto govori o verziji
$tld_array = array_slice($tld_array, 1); 
$tld_array = array_map('strtolower', $tld_array);
$tld_array = array_map('trim', $tld_array);


// $curl = curl_init();

// curl_setopt_array(
//     $curl,
//     array(
//         CURLOPT_URL => 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_ENCODING => '',
//         CURLOPT_MAXREDIRS => 10,
//         CURLOPT_TIMEOUT => 0,
//         CURLOPT_FOLLOWLOCATION => true,
//         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//         CURLOPT_CUSTOMREQUEST => 'GET',
//         CURLOPT_HTTPHEADER => array(
//             'Content-Type: application/json',
//             'Accept: application/json'
//         ),
//     )
// );


