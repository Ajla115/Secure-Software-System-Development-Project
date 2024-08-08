<?php
//all tasks work properly, and return {"success" : "true"}
$task2 = new CurlUtil();

$task2->getRequestWithCustomHeaders();
$task2->postRequestWithJSONData("John", "john@example.com");
$task2->deleteRequest();
$task2->putRequest("Jane", "jane@example.com");
$task2->patchRequest("active");
$task2->putRequestWithJSONandCustomHeaders("dark", "enabled");

class CurlUtil
{

    //GET Request with Custom Headers
    //Write a PHP script using cURL to make a GET request to https://api.example.com/data.
    //Add custom headers X-Custom-Header:Value1 and Authorization: BearerYourToken
    function getRequestWithCustomHeaders()
    {

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://en69fuytmprv.x.pipedream.net/data',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: BearerYourToken',
                    'X-Custom-Header:Value1',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }


    // POST Request with JSON Data:Create a PHP script that uses cURL to send a POST request to
    // https://api.example.com/users.The request should include
    // JSON data{"name":"John", "email":"john@example.com"}.
    // Set the appropriate Content-Type header.

    function postRequestWithJSONData($name, $email)
    {

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://en69fuytmprv.x.pipedream.net/users',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{"name": "' . $name . '","email":"' . $email . '"}',

                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    //this is how data is being sent, as JSON
                    'Accept: application/json'
                    //this tells that preffered response from server is also in JSON
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    // DELETE Request:Use PHP and cURL to send a DELETE request 
    // to https://api.example.com/users/123.
    // Ensure you handle the response to check if the deletion was successful.
    function deleteRequest()
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://en69fuytmprv.x.pipedream.net/users/123',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json'

                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    // PUT Request with Form Data: Write a PHP cURL script to send a
    // PUT request to https: //api.example.com/users/123. Update the user's data by sending form-encoded 
    // dataname=Jane&email=jane@example.com.
    function putRequest($name, $email)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://en69fuytmprv.x.pipedream.net/users/123',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => 'name="' . $name . '"&email="' . $email . '"',
                //there is no PUTFIELDS, or PATCHFIELDS, for everything only POSTFIELDS is
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    //It is important to put here this form x-www-form-url-encoded
                    'Accept: application/x-www-form-urlencoded'
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    //PATCH Request with Custom User Agent:Create a PHP script that makes a 
    // PATCH request to https://api.example.com/users/123, updating the user's status to active. 
    // Include a custom user agent MyCustomUserAgent/1.0.
    function patchRequest($status)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://en69fuytmprv.x.pipedream.net/users/123',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => '{"status" : "' . $status .'" }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
                CURLOPT_USERAGENT => "MyCustomUserAgent/1.0"
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    //PUT Request with JSON Data and Custom Headers: Create a PHP cURL script to
    //send a PUT request to https://api.example.com/settings/456. Update settings with
    //JSON data {"theme":"dark", "notifications":"enabled"}. Include a
    //custom header X-Request-ID: 789.

    function putRequestWithJSONandCustomHeaders($theme, $notifications){
        
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://en69fuytmprv.x.pipedream.net/settings/456',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => '{"theme": "' . $theme . '","notifications":"' . $notifications . '"}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-REQUEST-ID:789'
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    }





















?>