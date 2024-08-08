<?php


namespace Sssd;


use OpenApi\Annotations as OA;
use Flight as Flight;
use Firebase\JWT\JWT; //ovo creates JWT Token
use Firebase\JWT\Key;

require_once __DIR__ . '/../config_default.php';


class Controller
{
    /**
     * @OA\POST(
     * path="/register",
     * summary="Register User",
     * description="Register User",
     * tags={"Users"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below To Register a User",
     *    @OA\JsonContent(
     *       required={"fullname", "username", "password", "email", "phone"},
     *       @OA\Property(property="fullname", type="string", format="text",  example="Jane Doe"),
     *       @OA\Property(property="username", type="string", format="text",   example="user"),
     *       @OA\Property(property="password", type="string", format="text",   example="123456"),
     *       @OA\Property(property="email", type="email", format="text",  example="janedoe@example.com"),
     *       @OA\Property(property="phone", type="string", format="text",  example="+38761234567"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="User registered",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="User registered")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="User not registered",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="User not registered")
     *        )
     *     )
     * )
     */
    public function register()
    {
        //the register logic gets moved to the services
        $data = Flight::request()->data->getData();
        //this just accepts a JSON object

        //goes to the service to deal with the business logic
        //and here, then just output the result
        Flight::json(Flight::userservice()->register($data));

    }

    /**
     * @OA\POST(
     * path="/login",
     * summary="Login User",
     * description="Login User with Username/Email and Password",
     * tags={"Users"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below To Log In",
     *    @OA\JsonContent(
     *       required={ "username", "password"},
     *       @OA\Property(property="username", type="string", format="text",  example="user"),
     *       @OA\Property(property="password", type="string", format="text",  example="123456"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="User logged in",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="User logged in")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="User not logged in",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="User not logged in")
     *        )
     *     )
     * )
     */



    public function login()
    {

        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->login($data));

    }


    /**
     * @OA\POST(
     * path="/choosetwofactormethod",
     * summary="Choose 2FA Method",
     * description="Enter option to proceed with MFA",
     * tags={"Users"},
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below",
     *    @OA\JsonContent(
     *       required={ "fa_method"},
     *       @OA\Property(property="fa_method", type="string", format="text",  example="SMS"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully chosen option",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="2FA code should be sent to you")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Option not chosen",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Error")
     *        )
     *     )
     * )
     */

    public function choosetwofactormethod()
    {
        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->choosetwofactormethod($data));
    }

    /**
     * @OA\POST(
     * path="/entertwofactormethodcode",
     * summary="Enter 2FA Method Code",
     * description="Enter received 2FA Code",
     * tags={"Users"},
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below",
     *    @OA\JsonContent(
     *       required={ "otp_code"},
     *       @OA\Property(property="otp_code", type="string", format="text",  example="789567"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully logged in",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="2FA Code is correct")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Option not chosen",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Error")
     *        )
     *     )
     * )
     */

    public function entertwofactormethodcode()
    {

        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->entertwofactormethodcode($data));

    }


    /**
     * @OA\POST(
     * path="/changetwofactormethod",
     * summary="Reset 2FA",
     * description="Generate new 2FA code via new link",
     * tags={"Users"},
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     *    response=200,
     *    description="Successfully updated 2FA",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="New 2FA should be set up.")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Something failed",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Error")
     *        )
     *     )
     * )
     */


    public function changetwofactormethod()
    {
        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->changetwofactormethod($data));
    }

    /**
     * @OA\POST(
     * path="/changepassword",
     * summary="Change your password",
     * description="Enter your old password, new password, and repeat new password",
     * tags={"Users"},
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below",
     *    @OA\JsonContent(
     *       required={ "password", "new_password", "repeat_password"},
     *       @OA\Property(property="password", type="string", format="text",  example="user12345"),
     *       @OA\Property(property="new_password", type="string", format="text",  example="12345user"),
     *      @OA\Property(property="repeat_password", type="string", format="text",  example="12345user")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully changed your password",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="Password changed")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Failed Operation",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Password is not changed.")
     *        )
     *     )
     * )
     */
    public function changepassword()
    {
        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->changepassword($data));
    }


    /**
     * @OA\POST(
     * path="/forgetpassword",
     * summary="Change your password via forget option",
     * description="Enter your email to reset password",
     * tags={"Users"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below",
     *    @OA\JsonContent(
     *       required={ "email"},
     *       @OA\Property(property="email", type="string", format="text",  example="janedoe@gmail.com")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully sent an email to reset password",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="Email is sent")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Failed Operation",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Email has not been sent.")
     *        )
     *     )
     * )
     */


    public function forgetpassword()
    {
        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->forgetpassword($data));
    }

    /**
     * @OA\POST(
     * path="/verify",
     * summary="Verify registration",
     * description="Verify user registration via email",
     * tags={"Users"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below",
     *    @OA\JsonContent(
     *       required={ "register_token", },
     *       @OA\Property(property="register_token", type="string", format="text",  example="user_12345678910"),
     *       
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully verified user",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="User is verified")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Failed Operation",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="User is not verified.")
     *        )
     *     )
     * )
     */

    public function verifyuserviaemail()
    {
        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->verifyuserviaemail($data));
    }


    /**
     * @OA\POST(
     * path="/changepasswordthroughforget",
     * summary="Change password via forget flow",
     * description="Change password via forget flow",
     * tags={"Users"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below",
     *    @OA\JsonContent(
     *       required={ "activation_token", "new_password", "repeat_token" },
     *       @OA\Property(property="activation_token", type="string", format="text",  example="aYojdbjdjndknduwjk.sjnckdkiIENDKMDNSIDH.hbjdsodndi"),
     *       @OA\Property(property="new_password", type="string", format="text",  example="user12345"),
     *      @OA\Property(property="repeat_password", type="string", format="text",  example="user12345")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully changed password",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="Password is changed")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Failed Operation",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Password is not changed")
     *        )
     *     )
     * )
     */

    public function changepasswordthroughforget()
    {
        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->changepasswordthroughforget($data));
    }

     /**
     * @OA\POST(
     * path="/showrecoverycodes",
     * summary="Show recovery codes",
     * description="Show recovery codes as a replacement for 2FA",
     * tags={"Users"},
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     *    response=200,
     *    description="Successfully created recovery codes.",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="Recovery codes are created.")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Failed Operation",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Recovery codes are not created.")
     *        )
     *     )
     * )
     */
    
    public function showrecoverycodes()
    {
        //$data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->showrecoverycodes());
    }


    /**
     * @OA\POST(
     * path="/enterrecoverycodes",
     * summary="Enter recovery code",
     * description="Enter recovery code as a replacement for 2FA",
     * tags={"Users"},
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Provide All Info Below",
     *    @OA\JsonContent(
     *       required={ "recovery_code" },
     *       @OA\Property(property="recovery_code", type="string", format="text",  example="abDf-3F4"),
     *      
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully logged in using recovery code.",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="false"),
     *       @OA\Property(property="message", type="string", example="Recovery code successfully used.")
     *        )
     *     ),
     *   @OA\Response(
     *    response=500,
     *    description="Failed Operation",
     *    @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="Recovery code failed.")
     *        )
     *     )
     * )
     */

    public function enterrecoverycodes()
    {
        $data = Flight::request()->data->getData();
        Flight::json(Flight::userservice()->enterrecoverycodes($data));
    }





}
