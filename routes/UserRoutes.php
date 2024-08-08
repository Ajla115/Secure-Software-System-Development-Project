<?php
require_once "../api/Controller.php";


Flight::route('POST /register', function () {

    $controller = new sssd\Controller();
    $controller->register();

    //thiss sssd\ here is a namespace, but it is seen as a package with its functionalities
    //so when I call sssd\Controller, it works in a way that it calls Controller which is under package sssd

});

Flight::route('POST /login', function () {

    $controller = new sssd\Controller();
    $controller->login();

});


Flight::route('POST /choosetwofactormethod', function () {

    $controller = new sssd\Controller();
    $controller->choosetwofactormethod();

});

Flight::route('POST /entertwofactormethodcode', function () {

    $controller = new sssd\Controller();
    $controller->entertwofactormethodcode();

});

//this is to generate new QR code on the home page
Flight::route('POST /changetwofactormethod', function () {

    $controller = new sssd\Controller();
    $controller->changetwofactormethod();

});

//this is to change password on the home page
Flight::route('POST /changepassword', function () {

    $controller = new sssd\Controller();
    $controller->changepassword();

});

//this is for forget password flow to send an email
Flight::route('POST /forgetpassword', function () {

    $controller = new sssd\Controller();
    $controller->forgetpassword();

});


//this is to confirm register verification through email
Flight::route('POST /verify', function () {

    $controller = new sssd\Controller();
    $controller->verifyuserviaemail();

});

Flight::route('POST /changepasswordthroughforget', function () {

    $controller = new sssd\Controller();
    $controller->changepasswordthroughforget();

});


Flight::route('POST /showrecoverycodes', function () {

    $controller = new sssd\Controller();
    $controller->showrecoverycodes();

});

Flight::route('POST /enterrecoverycodes', function () {

    $controller = new sssd\Controller();
    $controller->enterrecoverycodes();

});







