<?php
require_once 'BaseService.php';
require_once __DIR__ . "/../dao/UserDao.php";
include '../vendor/autoload.php';
//include "./validtlds.php";
include (__DIR__ . '/validtlds.php');

use OTPHP\TOTP;
use Firebase\JWT\JWT; //ovo creates JWT Token
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//in the services, I always output the result through just return
//there is no need, for another Flight::json, because controller takes output from service
//and then it stores it into its own json
class UserService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new UserDao);
    }

    //check if password was pawned will be a seperate function because 
    //it will be used for the change your password flow
    //so, I want to have reusable code 
    //check if the password is eligible to be used based on info from HIBP
    private function checkPassword($password)
    {
        //start with the assumption, that the password is not pawned, 
        //if the password is pawned, output an appropriate message
        $pawned = false;

        $sha1Password = strtoupper(sha1($password));
        $prefix = substr($sha1Password, 0, 5);
        $suffix = substr($sha1Password, 5);
        $ch = curl_init("https://api.pwnedpasswords.com/range/" . $prefix);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false) {
            // Handle error; the request failed
            exit('Could not retrieve data from the API.');
        }

        if (str_contains($response, $suffix)) {
            $pawned = true;
        }

        return $pawned;
    }

    //I wrote this as well, as reusable funtion because I will need it for changing the password
    private function hashPassword($password)
    {
        //encrypt the password, that is not pawned using Bcrypt alg
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $hashedPassword;
    }


    private function checkExistenceForEmail($username)
    {
        $emailExistence = Flight::userdao()->checkExistenceForEmail($username);
        return $emailExistence;
    }

    private function checkExistenceForUsername($username)
    {
        $usernameExistence = Flight::userdao()->checkExistenceForUsername($username);
        return $usernameExistence;
    }

    //get the hashed password from the database4
    private function getPassword($username, $emailVal)
    {
        //depending, if the user is trying to log in with the correct email/username
        //an appropriate way will be chosen to retrieve password
        if ($emailVal) {
            $password = Flight::userdao()->getUserByEmail($username);
        } else if (!($emailVal)) {
            $password = Flight::userdao()->getUserByUsername($username);
        }

        return $password;

    }

    private function generateOTPassword()
    {
        // A random secret will be generated from this.
        // You should store the secret with the user for verification.
        //The secret is a seperate function because it will be used for sending by email and password
        $otp = TOTP::generate();
        return $otp->getSecret();
        //echo "The OTP secret is: {$otp->getSecret()}\n";
    }

    public function generateQrCode($secret, $username)
    {
        $otp = TOTP::createFromSecret($secret);

        $otp->setLabel($username . '@SSSD-PROJECT');
        $grCodeUri = $otp->getQrCodeUri(
            'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M',
            '[DATA]'
        );
        //echo "<img src='{$grCodeUri}'>";
        return $grCodeUri;
    }

    private function updateLoginCount($email)
    {

        Flight::userdao()->updateLoginCount($email);

    }

    public function createOTPCode($secret)
    {
        //data that the user enters is their otp from the phone
        $otp_code = TOTP::createFromSecret($secret);
        //echo "The current OTP is: {$otp->now()}\n";
        return $otp_code->now();
    }

    private function checkEmail($email)
    {
        global $tld_array;
        //this is how it s recognizing varaible from another file

        //this function checks if the email is just in a valid form with one @ and then it goes .com or . something else
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            //echo "Invalid email format";
            //exit('Prva funkcija pada');
            return false;
        }

        //Now, if the form of the email is correct, pursue further by checking the TLD of the email
        //here, file validtlds.php has to be included
        //I will take the last part of the current email and compare to see if it exists in the array of valid TLDs
        //I will separate the email into array by using explode function
        //Then I will take TLD, by applying end function to the array
        //End function works only for an array, and not for the string
        $email_array = explode(".", $email);
        $email_tld = end($email_array);
        // foreach($tld_array as $x){
        //     echo $x;
        // }

        //Here, it will be compared against imported list of valid TLDs from another file
        if (!in_array($email_tld, $tld_array)) {

            return false;
            //exit("Druga funkcija pada");
            //this means that the TLD that we are using is not valid, so there is no need to continue working further
        }
        //echo("Druga funkcija je prosla.");

        //however, if this above would return true, meaning our email TLD is correct, then we can continue to thr third step
        //That would be validation of the MX record
        //here for the domain, take the email part after the @ sign
        $domain_array = explode('@', $email);
        $domain = $domain_array[1];
        if (getmxrr($domain, $mx_details)) {
            //This part would return us all servers where the domain is used,
            //And it is not necessary here
            // foreach ($mx_details as $key => $value) {
            //     echo "$key => $value <br>";
            // }
            if (count($mx_details) > 0) {
                return true;
                //exit("Prosao MX.");
            } else {
                return false;
                //exit("Pao MX");
            }

        }

        //echo("sve je proslo");


    }

    private function checkPhoneNumber($phone)
    {
        $phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $number_proto = $phone_util->parse($phone, "BA");
            if ($phone_util->getNumberType($number_proto) === \libphonenumber\PhoneNumberType::MOBILE) {
                //echo "Mobile phone number\n";
                return true;
            } else {
                //in cases where the phone number is not correct exit and show an error message
                //exit("Not mobile phone number\n");
                return false;
            }
        } catch (\libphonenumber\NumberParseException $e) {
            // exit($e->getMessage());
            return false;
        }
    }

    private function send_email($subject, $body, $email, $recipientName)
    {
        $mail = new PHPMailer(true);
        try {
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
            $mail->SMTPDebug = SMTP::DEBUG_OFF; //Enable verbose debug output
            //ovo debug off sam stavila da nemam onaj ogromni output, sta se desava u svakoj sekundi slanja emaila

            $mail->isSMTP(); //Send using SMTP
            $mail->Host = SMTP_HOST; //Set the SMTP server to send through
            $mail->SMTPAuth = true; //Enable SMTP authentication
            $mail->Username = SMTP_USERNAME; //SMTP username
            $mail->Password = SMTP_PASSWORD; //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
            $mail->Port = SMTP_PORT;
            //465, TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //port 587 mi ovdje nije radio iako mi je bilo preporuceno da njega stavim
            //Recipients
            $mail->setFrom('example@gmail.com', 'SSSD App');
            //CHANGE example@gmail.com with your actual email
            $mail->addAddress($email, $recipientName); //Add a recipient
            //Content
            $mail->isHTML(true); //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            $mail->send();
            //echo 'Message has been sent';
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n");
            //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    private function checkPlusSign($phone)
    {
        //substr(string, starting_index, length)
        $result = substr($phone, 0, 1) === '+';
        return $result;
    }

    public function captcha()
    {
        $data = ['secret' => HCAPTCHA_SERVER_SECRET, 'response' => $_POST['h-captcha-response']];
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);
        $responseData = json_decode($response);
        if ($responseData->success) {
            return true;
        } else {
            return false;
        }
    }

    public function enterotp($jwt, $passcode)
    {
        //dekodirati JWT token i na osnovu podataka izvuci usera iz baze i njegov jwt token
        $decoded = (array) JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));

        //the third item in the array is the email
        //based on the email, I will extract the whole user
        //and then just take their secret
        $user = $this->checkExistenceForEmail($decoded[2]);

        $secret = $user['secret'];

        //now create the otp from that secret
        //and verify that secret againt what the user has enetered
        $otp = TOTP::createFromSecret($secret);
        //verify method returns true or false
        return $otp->verify($passcode);
        //if the MFA is correct
        //provide new jwt token and let te user log in
    }

    public function send_message($phone_number, $message)
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
                    'Authorization: App ' . TEXT_MESSAGE_API_KEY,
                    //this part after App, is my api url from infoBip page
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            )
        );

        $response = curl_exec($curl);
        curl_close($curl);
        //echo $response;
    }

    private function updatePassword($password, $email)
    {
        $result = Flight::userdao()->updatePassword($password, $email);
        return $result;
    }




    public function register($data)
    {

        //this is now in controller
        //$data = Flight::request()->data->getData();
        //this accepts a JSON object

        //extract individual attributes from JSON object
        $fullname = $data['fullname'];
        $username = $data['username'];
        $password = $data['password'];
        $email = $data['email'];
        $phone = $data['phone'];
        $reserved_names = array("admin", "root", "system", "administrator");



        if (mb_strlen($username) <= 3) {
            Flight::halt(500, "The username should be longer than 3 characters.");
            // return array("status" => 500, "message" => "The username should be longer than 3 characters.");
            //echo "The username should be longer than 3 characters.";
            //exit;
            //exit works as a break in other languages
        }

        if (!ctype_alnum($username)) {
            Flight::halt(500, "The username can only contain letters and numbers, no special characters and spaces.");
            return array("status" => 500, "message" => "The username can only contain letters and numbers, no special characters and spaces.");
            // echo "The username can only contain letters and numbers, no special characters and spaces.";
            //exit;
        }


        //stripos is case-insensitive, so it will take care of both upper and lower case letters
        //it accepts the whole string, and what we are looking to find it
        //it returns index of the first occurence

        foreach ($reserved_names as $reserved_name) {
            if (stripos($username, $reserved_name) !== false) {
                Flight::halt(500, "A reserved name can't be used as username.");
            }
        }


        if (mb_strlen($password) < 8) {

            Flight::halt(500, "The password should be at least 8 characters long");
            //return array("status" => 500, "message" => "The password should be at least 8 characters long");
            //echo "The password should be at least 8 characters long";
            //exit;
        }

        $emailResult = $this->checkEmail($email);

        if (!$emailResult) {
            Flight::halt(500, "Email input invalid");
            //if the value returned from the email function is false, just break here
            //with email error message, and otherwise just continue
            //return array("status" => 500, "message" => "Email input invalid");
            //exit('Email input invalid');
        }


        //If result of phone check is true, then continue doing further
        $result = $this->checkPhoneNumber($phone);
        if (!$result) {
            Flight::halt(500, "Phone number input invalid");
            //return array("status" => 500, "message" => "Phone number input invalid");
            //exit('Phone number input invalid');

        }

        //now, when we have checked that the phone number is in correct Bosnian form
        //I will check if the phone has that + sign in front of it.

        if (!($this->checkPlusSign($phone))) {
            Flight::halt(500, "Please put a + sign in front of the phone number.");
        }

        $pawned = $this->checkPassword($password);
        //if the result is true, notify the user that the password is pawned and abort the mission
        //if the password is not pawned, hash it and store into database

        if ($pawned) {
            Flight::halt(500, "Password is pawned. Use another password.");
            //return array("status" => 500, "message" => "Password is pawned. Use another password.");
        } else {

            $hashedPassword = $this->hashPassword($password);

            //change the value of the JSON object that contains password
            $data["password"] = $hashedPassword;

            //after hashing password, generate the OTP password as secret
            $secret = $this->generateOTPassword();
            $login_count = 0;

            //now, I have added these two new elements to the existing array and sent that to the database to be inserted
            $data["secret"] = $secret;
            $data["login_count"] = $login_count;

            //until the user clicks on the confirmation link, it will be unverified
            $data["verified"] = "unverified";

            //I am using uniqID function to create unique identifiers based on microseconds
            //Plus I added user_ and true parameter for more entropy to increase uniqnuess
            $register_token = uniqid('user_', true);
            $data["register_token"] = $register_token;


            //$hostname = gethostname();
            // Return the URL of the verification endpoint with the verification token as a parameter
            //$rootFolder = basename($_SERVER['DOCUMENT_ROOT']);
            //$projectFolder = basename(dirname(dirname(__DIR__)));


            $daoResult = parent::add($data);
            if ($daoResult["status"] == 500) {
                Flight::halt(500, $daoResult["message"]);
            }

            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $url = "https://";
            } else {
                $url = "http://";
            }

            //ovo ce biti localhost, ili www.example.com, etc..
            $url .= $_SERVER['HTTP_HOST'];

            // Ovo, mi ovdje sad ne treba jer ce mi ovo dati current route, a ja necu tu vec drugu
            //$url .= $_SERVER['REQUEST_URI'];
            $projectFolder = basename(dirname(__DIR__));
            $verificationPath = '/' . $projectFolder . '/front/verify.html';
            $verificationLink = $url . $verificationPath . '?register_token=' . $register_token;

            $verificationLink = $url . $verificationPath . '?register_token=' . $register_token;
            $subject = "Confirm Register Verification";
            $body = "Please click the following link to verify your registration:<br><a href='$verificationLink'>Verify Registration By Clicking Here</a>";


            $this->send_email($subject, $body, $email, $fullname);

            $daoResult["message"] .= " Confirm your account registration through email.";
            return array("status" => $daoResult["status"], "message" => $daoResult["message"]);
        }
    }

    private function trackFailedAttempts($ipAddress)
    {

        Flight::userdao()->trackFailedAttempts($ipAddress);

    }

    private function resetFailedAttempts($ipAddress)
    {
        Flight::userdao()->resetFailedAttempts($ipAddress);

    }

    public function numberOfFailedAttempts($ipAddress)
    {
        return Flight::userdao()->numberOfFailedAttempts($ipAddress);
    }

    public function verifyCaptcha($captchaResponse)
    {
        $data = [
            'secret' => HCAPTCHA_SERVER_SECRET,
            'response' => $captchaResponse
            //'response' => $_POST['h-captcha-response']
        ];
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);
        $responseData = json_decode($response);
        if ($responseData->success) {
            return true;
        } else {
            return false;
        }
    }

    public function login($data)
    {
        $username = $data['username'];
        //here, we will check if username is actually username or email
        $password = $data['password'];

        $ipAddress = $_SERVER['REMOTE_ADDR'];

        $captchaResponse = isset($data['h-captcha-response']) ? $data['h-captcha-response'] : '';

        if ($this->numberOfFailedAttempts($ipAddress)) {
            if (!$this->verifyCaptcha($captchaResponse)) {
                Flight::halt(500, json_encode(["message" => "Captcha validation failed.", "captchaRequired" => true]));
            } else {
                $this->resetFailedAttempts($ipAddress);
            }
        }


        if ($username == '' || $password == ' ') {
            Flight::halt(500, "All fields have to be filled in.");
            //return ("All fields have to be filled in.");
        }


        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            //second way would be just to check if username contains @ letter
            //$message = "User is logging in with email.";
            $emailVal = true;
        } else {
            //$message = "User is logging in with username.";
            $emailVal = false;
        }

        //if user is logging in with email, I will first check if this email exists
        //if it, exists we will retrieve its password and verify it
        //if the email does not exist, I will show appropriate message on the screen
        if ($emailVal) {

            $user = $this->checkExistenceForEmail($username);
            //return $user; -> it is better to return the whole object and then just do slicing here in services


            //if null gets returned or the email does not exist
            if (!$user || $user['email'] != $username) {
                //this means that user is not logging in using a correct email, so output an appropriate message
                //return array("status" => 500, "message" => "This email does not exist");
                $this->trackFailedAttempts($ipAddress);

                Flight::halt(500, "This email does not exist.");

            }
        }

        //now if, it is logging in using username
        else if (!$emailVal) {

            $user = $this->checkExistenceForUsername($username);
            if (!$user || $user['username'] != $username) {
                $this->trackFailedAttempts($ipAddress);
                Flight::halt(500, "This username does not exist.");

                //return array("status" => 500, "message" => "This username does not exist");
            }
        }

        //After, we have checked if the username or email are valid in the database
        //It is time to check the password
        //$hashedPassword = $this->getPassword($username, $emailVal);

        if (!isset($user['password']) || !password_verify($data["password"], $user['password'])) {

            //if the passwords do not match, output an appropriate message
            $this->trackFailedAttempts($ipAddress);

            Flight::halt(500, "Invalid password.");

            //return array("status" => 500, "message" => "Invalid password");

        } else if ($user["verified"] == "unverified") {
            $this->trackFailedAttempts($ipAddress);
            Flight::halt(500, "Unverified user.");

        }


        //if the password match, check if the user is only logging in for the first time
        //if it is logging for the first time, then only generate the qr code

        $secret = $user['secret'];
        //to create jwt token, I removed id and password from token
        $coded_user = [$user['fullname'], $user['username'], $user['email'], $user['phone'], $user['secret'], $user['login_count']];


        $this->resetFailedAttempts($ipAddress);


        $token = JWT::encode($coded_user, JWT_SECRET, 'HS256');

        if ($user['login_count'] == 0) {
            //here, also create JWT Token, so its data can be extracted for the validation of OTP
            $qrLink = $this->generateQrCode($secret, $user['username']);
            return array("status" => 200, "message" => "Scan the QR code and enter the OTP.", "link" => $qrLink, "token" => $token);
        } else {
            // Allow login only if login_count is greater than zero
            if ($user['login_count'] > 0) {

                return array("status" => 200, "message" => "Successful login.", "token" => $token);
            } else {
                Flight::halt(500, "Login not allowed. Please scan the QR code first.");
            }
        }
    }


    public function choosetwofactormethod($data)
    {
        $headers = getallheaders();
        $token = '';


        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            list($type, $token) = explode(' ', $authHeader, 2);


            if (strcasecmp($type, 'Bearer') == 0 && !empty($token)) {
                try {
                    // Decode the token
                    $decodedUser = (array) JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
                    //print_r($decodedUser);
                    //SECRET -> index 4
                    //Phone -> index 3 
                    //EMAIL -> index 2

                    $user_phone = $decodedUser[3];
                    $user_email = $decodedUser[2];
                    $user_secret = $decodedUser[4];
                    $otp_code = $this->createOTPCode($user_secret); //this will be the message

                    if (isset($data['fa_method'])) {
                        $method = $data['fa_method'];
                        if ($method === 'SMS') {
                            // Call method to send secret through SMS

                            $this->send_message($user_phone, $otp_code);

                            // Call method to send secret through SMS
                            return array("status" => 200, "message" => "Code will be sent via SMS. Code is valid for 30 seconds.");
                        } elseif ($method == 'OTP') {
                            // Enter code from QR scanned on phone 
                            return array("status" => 200, "message" => "Code can be found on QR Code scanner App. Code is valid for 30 seconds.");
                        } elseif ($method === 'EMAIL') {
                            $subject = "2F Authentication with Email";
                            $body = "This code is valid for 30 seconds: " . $otp_code;
                            $this->send_email($subject, $body, $user_email, $decodedUser[0]);
                            return array("status" => 200, "message" => "Code will be sent via EMAIL. Code is valid for 30 seconds.");

                        } else {
                            Flight::halt(500, "Unknown 2FA Method");
                        }
                    } else {
                        Flight::halt(500, "Invalid input data.");
                    }
                } catch (Exception $e) {
                    Flight::halt(401, 'Invalid token.');
                }
            } else {
                Flight::halt(401, 'Invalid Authorization header format.');
            }
        } else {
            Flight::halt(401, 'Authorization header not found.');
        }
    }

    public function entertwofactormethodcode($data)
    {

        $user_otp = $data["otp_code"];

        $headers = getallheaders();
        $token = '';


        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            list($type, $token) = explode(' ', $authHeader, 2);


            if (strcasecmp($type, 'Bearer') == 0 && !empty($token)) {
                try {

                    $decodedUser = (array) JWT::decode($token, new Key(JWT_SECRET, 'HS256'));

                    $fullname = $decodedUser[0];
                    $username = $decodedUser[1];
                    $email = $decodedUser[2];
                    $phone = $decodedUser[3];
                    $user_secret = $decodedUser[4];
                    //$login_count = $decodedUser[5];

                    $server_otp_code = $this->createOTPCode($user_secret);

                    //after successful 2FA, we will delete the first jwt token
                    //and create the second one

                    $coded_user2 = [$fullname, $username, $email, $phone, $user_secret];

                    $token = JWT::encode($coded_user2, JWT_SECRET, 'HS256');

                    if (hash_equals($server_otp_code, $user_otp)) {
                        $this->updateLoginCount($email);
                        return array("status" => 200, "message" => "2FA Check is successful.", "token2" => $token);
                    } else {
                        Flight::halt(500, "2FA Check is not successful.");
                    }
                } catch (Exception $e) {
                    Flight::halt(401, 'Invalid token.');
                }
            } else {
                Flight::halt(401, 'Invalid Authorization header format.');
            }
        } else {
            Flight::halt(401, 'Authorization header not found.');
        }


    }

    private function updateSecretValue($newSecret, $username)
    {
        Flight::userdao()->updateSecretValue($newSecret, $username);

    }


    public function changetwofactormethod()
    {
        $headers = getallheaders();
        $token = '';

        //First, i will check if I am properly sending value with authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            list($type, $token) = explode(' ', $authHeader, 2);

            // then I will also check if that value is of tpe Bearer + token
            if (strcasecmp($type, 'Bearer') == 0 && !empty($token)) {
                try {

                    $decodedUser = (array) JWT::decode($token, new Key(JWT_SECRET, 'HS256'));

                    $username = $decodedUser[1];

                    //now it will create a new secret
                    $newSecret = $this->generateOTPassword();


                    //and update the value of secret in the database
                    $this->updateSecretValue($newSecret, $username);

                    $newQrCode = $this->generateQrCode($newSecret, $username);

                    return array("status" => 200, "message" => "Click on the new link down below.", "link" => $newQrCode);

                } catch (Exception $e) {
                    Flight::halt(401, "Not possible to generate new link.");
                }
            } else {
                Flight::halt(401, 'Invalid Authorization header format.');
            }
        } else {
            Flight::halt(401, 'Authorization header not found.');
        }

    }


    public function changepassword($data)
    {


        $headers = getallheaders();
        $token = '';


        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            list($type, $token) = explode(' ', $authHeader, 2);


            if (strcasecmp($type, 'Bearer') == 0 && !empty($token)) {
                try {

                    if (!isset($data['password']) || !isset($data['new_password']) || !isset($data['repeat_password'])) {
                        Flight::halt(500, "Fields cannot be empty.");
                    }


                    $decodedUser = (array) JWT::decode($token, new Key(JWT_SECRET, 'HS256'));

                    //$user_email = $decodedUser[2];
                    //now I will extract all data about the user just based onn the email, using already created function
                    $user = $this->checkExistenceForEmail($decodedUser[2]);


                    //$user_password = $user["password"];
                    //first compare, if the password saved in the database is it same with the password that the user entered for old value
                    if (!password_verify($data["password"], $user["password"])) {
                        Flight::halt(500, "Password does not match saved password.");
                    }

                    //check if the new and repeated password are the same
                    if (!hash_equals($data["new_password"], $data["repeat_password"])) {
                        Flight::halt(500, "New and repeated password are not the same.");
                    }

                    //now check if the new password  fits the criteria
                    if (mb_strlen($data["new_password"]) < 8) {
                        Flight::halt(500, "The password should be at least 8 characters long");
                    }

                    $pawned = $this->checkPassword($data["new_password"]);

                    if ($pawned) {
                        Flight::halt(500, "Password is pawned. Use another password.");
                    } else {

                        $hashedPassword = $this->hashPassword($data["new_password"]);

                        //this decodedUser[2] is the email so I am sending it as a verification to know whose user's password I am updating because emails are unique
                        $daoResult = $this->updatePassword($hashedPassword, $decodedUser[2]);
                        if ($daoResult["status"] == 500) {
                            Flight::halt(500, $daoResult["message"]);
                            //for successul password change, send an email
                        } else if ($daoResult["status"] == 200) {
                            $subject = "Successul password change.";
                            $body = "Your password has been successfully updated.";
                            $this->send_email($subject, $body, $decodedUser[2], $decodedUser[0]);
                        }
                        return array("status" => $daoResult["status"], "message" => $daoResult["message"]);

                    }

                } catch (Exception $e) {
                    Flight::halt(401, "Not possible to update password");
                }
            } else {
                Flight::halt(401, 'Invalid Authorization header format.');
            }
        } else {
            Flight::halt(401, 'Authorization header not found.');
        }


    }

    private function checkaccountverification($email)
    {
        $result = Flight::userdao()->checkaccountverification($email);
        return $result;
    }

    public function verifyuserviaemail($data)
    {
        $result = Flight::userdao()->verifyuserviaemail($data["register_token"]);

        if ($result["status"] !== 200) {
            Flight::halt($result["status"], $result["message"]);
        } else {
            return array($result["status"], $result["message"]);
        }

    }


    private function getForgetPasswordCount($email)
    {
        return Flight::userdao()->getForgetPasswordCount($email);
    }

    private function incrementForgetPasswordCount($email)
    {
        Flight::userdao()->incrementForgetPasswordCount($email);
    }

    private function resetForgetPasswordCount($email)
    {
        Flight::userdao()->resetForgetPasswordCount($email);
    }

    private function checkTimeForRequests($email)
    {
        return Flight::userdao()->checkTimeForRequests($email);
    }

    private function getName($email)
    {
        return Flight::userdao()->getName($email);
    }

    public function forgetpassword($data)
    {

        //first check if email is in the correct form
        if (!($this->checkEmail($data["email"]))) {
            Flight::halt(500, "Invalid email format.");
        }

        //now check if user with this email exists
        if (!($this->checkExistenceForEmail($data["email"]))) {
            Flight::halt(500, "Email does not exist.");
        }

        $verificationResult = $this->checkaccountverification($data["email"]);

        if ($verificationResult["status"] !== 200) {
            Flight::halt($verificationResult["status"], $verificationResult["message"]);
        }

        $recipientName = $this->getName($data["email"]);


        //for two tries in ten minutes
        if ($this->checkTimeForRequests($data["email"])) {
            Flight::halt(500, "You can only make two requests within 10 minutes. Please try again later.");
        }

        //create requirements for captcha
        $forgetPasswordCount = $this->getForgetPasswordCount($data["email"]);

        if ($forgetPasswordCount >= 2) {
            $captchaResponse = isset($data['h-captcha-response']) ? $data['h-captcha-response'] : '';
            if (!$this->verifyCaptcha($captchaResponse)) {
                Flight::halt(500, json_encode(["message" => "Captcha validation failed.", "captchaRequired" => true]));
            } else {
                $this->resetForgetPasswordCount($data["email"]);
            }
        }

        // Increment forget password count
        $this->incrementForgetPasswordCount($data["email"]);


        //When, all requirements have been satisified, then create a JWT token with expiration

        $issue_time = time(); //issued at
        $expiration_time = $issue_time + 300;  //300 seconds are 5 minutes
        $userData = [
            'email' => $data['email'],
            'exp' => $expiration_time,
            'iat' => $issue_time  //I also added issued at claim
        ];

        $expirationJWT = JWT::encode($userData, JWT_SECRET, 'HS256');
        //now send an email to the user
        //$hostname =gethostname(); -> ovo bude nesto Desktop V6Q

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $url = "https://";
        } else {
            $url = "http://";
        }

        //examples of this are localhost, www.example.com, etc..
        $url .= $_SERVER['HTTP_HOST'];

        //this gives current route
        //$url .= $_SERVER['REQUEST_URI'];
        $projectFolder = basename(dirname(__DIR__));
        $activationPath = '/' . $projectFolder . '/front/changePassword.html';
        $activationLink = $url . $activationPath . '?activation_token=' . $expirationJWT;

        //now save token to the database, and put count to be 0 to make sure the token has not been used before
        $this->saveExpirationTokenAndCount($expirationJWT, $data["email"]);

        $subject = "Change your password";
        $body = "Please click the following link to change your password:<br><a href='$activationLink'>Change Password By Clicking Here</a>";
        $this->send_email($subject, $body, $data["email"], $recipientName);


        return array("status" => 200, "message" => "Check your email.");

    }

    private function saveExpirationTokenAndCount($expirationJWT, $email)
    {
        Flight::userdao()->saveExpirationTokenAndCount($expirationJWT, $email);

    }

    public function changepasswordthroughforget($data)
    {
        try {
            //first, I need to check if the token is valid AKA can it be related to any of the tokens in database
            $result = $this->checkTokenExistence($data["activation_token"]);

            //if the token does not exist, stop action
            if ($result["status"] !== 200) {
                Flight::halt($result["status"], $result["message"]);
            }

            //if received value is 1, you cannot no longer use it
            //if received value is 0, you can use it
            $result2 = $this->checkTokenCount($data["activation_token"]);

            if (isset($result2) && $result2["status"] !== 200) {
                Flight::halt($result2["status"], $result2["message"]);
            }

            if (isset($result3) && $result3["status"] !== 200) {
                Flight::halt($result2["status"], $result2["message"]);
            }

            $decoded = (array) JWT::decode($data["activation_token"], new Key(JWT_SECRET, 'HS256'));
            //$decoded["email"]

            //by default, it should give an error, if token has expired
            //if token exists, check if it has expired
            // $current_time = time();
            // if($decoded["exp"] < $current_time){
            //     Flight::halt(500, "Session has expired.");
            // }

            if (!isset($data['new_password']) || !isset($data['repeat_password'])) {
                Flight::halt(500, "Fields cannot be empty.");
            }

            //check if the new and repeated password are the same
            if (!hash_equals($data["new_password"], $data["repeat_password"])) {
                Flight::halt(500, "New and repeated password are not the same.");
            }

            //now check if the new password  fits the criteria
            if (mb_strlen($data["new_password"]) < 8) {
                Flight::halt(500, "The password should be at least 8 characters long");
            }

            $pawned = $this->checkPassword($data["new_password"]);

            if ($pawned) {
                Flight::halt(500, "Password is pawned. Use another password.");
            } else {

                $hashedPassword = $this->hashPassword($data["new_password"]);

                $recipientName = $this->getName($decoded["email"]);

                //this decodedUser[2] is the email so I am sending it as a verification to know whose user's password I am updating because emails are unique
                $daoResult = $this->updatePassword($hashedPassword, $decoded["email"]);
                if ($daoResult["status"] == 500) {
                    Flight::halt(500, $daoResult["message"]);
                    //for successul password change, send an email
                } else if ($daoResult["status"] == 200) {
                    $subject = "Successul password change.";
                    $body = "Your password has been successfully updated.";
                    $this->send_email($subject, $body, $decoded["email"], $recipientName);
                   
                }
                $result3 = $this->updateTokenCount($data["activation_token"]);
                return array("status" => $daoResult["status"], "message" => $daoResult["message"]);

            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            Flight::halt(500, json_encode([
                'error' => true,
                'message' => 'Token has expired.'
            ]));
        }
    }



    private function checkTokenExistence($activationToken)
    {
        return Flight::userdao()->checkTokenExistence($activationToken);
    }

    private function checkTokenCount($activationToken)
    {
        return Flight::userdao()->checkTokenCount($activationToken);
    }

    private function updateTokenCount($activationToken)
    {
        return Flight::userdao()->updateTokenCount($activationToken);
    }


    public function showrecoverycodes()
    {


        $headers = getallheaders();
        $token = '';


        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            list($type, $token) = explode(' ', $authHeader, 2);


            if (strcasecmp($type, 'Bearer') == 0 && !empty($token)) {
                try {

                    //if token exists, decode it
                    $decodedUser = (array) JWT::decode($token, new Key(JWT_SECRET, 'HS256'));

                    //call function that will create 5 recovery codes in the format xxxx-xxxx

                    $recoveryCodesArr = $this->createRecoveryCodes();
                    //this returns me associatove array, and I will put it into comma seperated list using implode

                    $values = array_values($recoveryCodesArr);

                    // Convert the array of values to a comma-separated string
                    $recoveryCodesList = implode(',', $values);

                    //now these recovery codes, will be saved to the database as comma seperated list

                    return $this->saveRecoveryCodes($recoveryCodesList, $decodedUser[2]);

                } catch (Exception $e) {
                    Flight::halt(401, "Not possible to generate new recovery codes");
                }
            } else {
                Flight::halt(401, 'Invalid Authorization header format.');
            }
        } else {
            Flight::halt(401, 'Authorization header not found.');
        }
    }

    private function createRecoveryCodes()
    {


        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $recoveryCodes = [];

        while (count($recoveryCodes) < 5) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                if ($i == 4) {
                    $code .= '-';
                } else {
                    $code .= $characters[rand(0, strlen($characters) - 1)];
                }
            }

            if (!in_array($code, $recoveryCodes)) {
                $recoveryCodes[] = $code;
            }
        }

        return $recoveryCodes;
    }


    private function saveRecoveryCodes($recoveryCodesArr, $email)
    {
        return Flight::userdao()->saveRecoveryCodes($recoveryCodesArr, $email);
    }


    public function enterrecoverycodes($data)
    {

        $headers = getallheaders();
        $token = '';

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            list($type, $token) = explode(' ', $authHeader, 2);


            if (strcasecmp($type, 'Bearer') == 0 && !empty($token)) {
                try {

                    if (!isset($data["recovery_code"])) {
                        Flight::halt(500, "Field is required.");
                    }

                    if (mb_strlen($data["recovery_code"]) > 8) {
                        Flight::halt(500, "Input is too long.");
                    }

                    $decodedUser = (array) JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
                    $fullname = $decodedUser[0];
                    $username = $decodedUser[1];
                    $email = $decodedUser[2];
                    $phone = $decodedUser[3];
                    $user_secret = $decodedUser[4];

                    //if it has passed basic validation, check if it exists,
                    $dbResult = $this->checkRecoveryCodeExistence($data["recovery_code"], $email);

                    //handle case if status is not 200

                    if ($dbResult["status"] != 200) {
                        Flight::halt($dbResult["status"], $dbResult["message"]);
                    }

                    //if status is 200, make a new token

                    $coded_user2 = [$fullname, $username, $email, $phone, $user_secret];

                    $token = JWT::encode($coded_user2, JWT_SECRET, 'HS256');

                    return array("status" => $dbResult["status"], "message" => $dbResult["message"], "token2" => $token);

                } catch (Exception $e) {
                    Flight::halt(401, "Not possible to use recovery codes.");
                }
            } else {
                Flight::halt(401, 'Invalid Authorization header format.');
            }
        } else {
            Flight::halt(401, 'Authorization header not found.');
        }



    }

    private function checkRecoveryCodeExistence($recovery_code, $email)
    {
        return Flight::userdao()->checkRecoveryCodeExistence($recovery_code, $email);
    }


}

































