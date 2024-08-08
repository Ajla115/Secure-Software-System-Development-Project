<?php
require("vendor/autoload.php");


$projectname = basename(dirname(__DIR__));
$htdocs = basename(dirname(dirname(__DIR__)));
$apache2 = basename(dirname(dirname(dirname(__DIR__))));
$wampstack = basename(dirname(dirname(dirname(dirname(__DIR__)))));
$bitnami = basename(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
$c = basename(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))));

// echo $projectname . "\n";
// echo $htdocs . "\n";
// echo $apache2 . "\n";
// echo $wampstack . "\n";
// echo  $bitnami . "\n";
// echo $c . "\n";

//$apiPath = 'C:\\' . $bitnami . '\\' . $wampstack . '\\' . $apache2 . '\\' . $htdocs . '\\' . $projectname . '\\api';
//$openapi = \OpenApi\Generator::scan([$apiPath]);
//C:\Bitnami\wampstack-8.1.2-0\apache2\htdocs\sssd-2024-21002935\api

//$apiPath = "C:\\" . $bitnami . "\\" . $wampstack . "\\" . $apache2 . "\\" .  $htdocs . "\\" . $projectname . "\\api";

//$openapi = \OpenApi\Generator::scan([$apiPath]);

//$openapi = \OpenApi\Generator::scan(['C:\Bitnami\wampstack-8.1.2-0\apache2\htdocs\sssd-2024-21002935\api']);

$openapi = \OpenApi\Generator::scan([ dirname(  __FILE__ ).'/api']);
header('Content-Type: application/json');
echo $openapi->toJson();