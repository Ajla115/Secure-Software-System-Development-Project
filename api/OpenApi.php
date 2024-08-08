<?php
namespace Sssd;

//namespaces are case-insensitive
//that is why in UserRoutes.php, they are declared with lower case sssd


use OpenApi\Annotations as OA;


/**
 * @OA\Info(
 *     description="SSSD project API",
 *     version="1.0.0",
 *     title="My first API",
 *     @OA\Contact(
 *         email="ajla.korman@stu.ibu.edu.ba"
 *     )
 * )
 * @OA\Server(
 *     description="API Mocking",
 *     url="http://127.0.0.1/sssd-2024-21002935/api"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

class OpenApi
{
    //This is my link to access my routes on the Swagger
    //http://127.0.0.1/sssd-2024-21002935/doc.php
}
