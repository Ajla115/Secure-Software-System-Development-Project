<?php

$text_message = new TextMessage();
$text_message->send_message("38762421745", "Hello, from Lab 1.");
//here, echo is not needed because there is echo inside of the function itself
//if there wasn't echo inside of function, here it would be needed

class TextMessage
{

    function send_message($phone_number, $message)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://y36vgd.api.infobip.com/sms/2/text/advanced',
                //this is the link that responds to the POST method
                //and it from page SMS Message InfoBip
                CURLOPT_RETURNTRANSFER => true,
                //this is used for the response, because we want to  catch this response
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                //this declared type of method
                CURLOPT_POSTFIELDS => '{"messages":[{"destinations":[{"to": "' . $phone_number . '"}],"from":"AjlaSMS","text": "' . $message . '"}]}',
                //here, I have to be careful with single and double quotes, because double ones are just for keywords, and the single ones are from the InfoBip SMS Method
                //single quotes are the closing quotes from the this SMS structure, and the double quotes are from the body elements
                //so, part " ' mean that I am opening " " quotes for the JSON object, that is value of phone number, however since I am concatinating that from outside
                //I first need to close outer ' from the method itself, concatinate variable, and repeat the process again with ' "
                CURLOPT_HTTPHEADER => array(
                    'Authorization: App 58130239d88de3a571b7f49dc2085b9d-acd5bb5a-08f4-4668-adc5-50785e8c0827',
                    //thispart after App, is my api url from infoBip page
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            )
        );

        $response = curl_exec($curl);
        curl_close($curl);
        echo $response;

    }

}









