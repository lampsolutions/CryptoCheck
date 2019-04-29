<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     description="CryptoCheck API
[https://www.cryptopanel.de/].",
 *     version="1.0.0",
 *     title="CryptoCheck API",
 *     termsOfService="",
 *     @OA\Contact(
 *         email="support@cryptopanel.de"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="http://opensource.org/licenses/MIT"
 *     )
 * )
 */
/**
 * @OA\SecurityScheme(
 *   securityScheme="api_key",
 *   type="apiKey",
 *   in="query",
 *   name="api_key"
 * )
 */
/**
 * @OA\Tag(
 *     name="Listener",
 *     description="",
 *     @OA\ExternalDocumentation(
 *         description="Find out more",
 *         url="https://www.cryptopanel.de/"
 *     )
 * )
 * @OA\Server(
 *     description="CryptoCheck API Endpoint",
 *     url="https://cryptocheck.api.cryptopanel.de"
 * )
 */
class Controller extends BaseController
{
    //
}
