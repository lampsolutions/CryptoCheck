<?php

namespace App\Http\Controllers;

use App\Jobs\NewBlockJob;
use App\Jobs\NewTransactionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Predis\Collection\Iterator\Keyspace;

class RegisterListenerController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/registerListener/callback/",
     *     tags={"Listener"},
     *     summary="Adds an listener job to get notified about specific events inside an blockchain via email",
     *     security={
     *         {"api_key": {}}
     *     },
     *     @OA\Parameter(
     *         name="address",
     *         in="query",
     *         description="Address to watch for incoming transactions",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="c",
     *         in="query",
     *         description="currency",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"BTC", "BCH", "LTC", "DASH"}
     *
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="confirmations",
     *         in="query",
     *         description="required confirmations of the transaction to receive transaction detail via http callback",
     *         required=true,
     *         @OA\Schema(
     *             type="number"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="url",
     *         in="query",
     *         description="Url address to receive transaction details",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     operationId="Info",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="bad request"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="internal server error"
     *     )
     * )
     *
     * @return mixed
     */
    public function registerListenerCallback(Request $request) {
        $address = $request->get('address');
        $currency = $request->get('c');
        $confirmations = (int)$request->get('confirmations');
        $url = $request->get('url');
        $ttl = false;

        $jobId = uniqid();

        Redis::connection()->sadd('txwatch_address:'.$currency.':'.$address, json_encode([
            'jobId' => $jobId.':'.$currency.':'.$address,
            'address' => $address,
            'currency' => $currency,
            'confirmations' => $confirmations,
            'url' => $url,
            'created' => time(),
            'ttl' => $ttl
        ]));


        return [
            'jobId' => $jobId.':'.$currency.':'.$address,
            'address' => $address,
            'currency' => $currency,
            'confirmations' => $confirmations,
            'created' => time(),
            'ttl' => $ttl
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/v1/removeListener/callback/",
     *     tags={"Listener"},
     *     summary="Removes an listener job",
     *     security={
     *         {"api_key": {}}
     *     },
     *     @OA\Parameter(
     *         name="address",
     *         in="query",
     *         description="Address to watch for incoming transactions",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="c",
     *         in="query",
     *         description="currency",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"BTC", "BCH", "LTC", "DASH"}
     *
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="jobId",
     *         in="query",
     *         description="jobId",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     operationId="Info",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="bad request"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="internal server error"
     *     )
     * )
     *
     * @return mixed
     */
    public function removeListenerCallback(Request $request) {
        $address = $request->get('address');
        $currency = $request->get('c');
        $jobId = $request->get('jobId');


        // Remove confirmed jobs
        $key_confirmed = 'txwatch_address_confirmed:'.$currency.':'.$address;
        $txWatchJobsConfirmed = Redis::connection()->smembers($key_confirmed);
        foreach ($txWatchJobsConfirmed as $idx => $txWatchJobConfirmedJson) {
            $txWatchJobConfirmed = json_decode($txWatchJobConfirmedJson, true);
            if($txWatchJobConfirmed['jobId'] == $jobId) {
                Redis::connection()->srem($key_confirmed, $txWatchJobConfirmedJson);
            }
        }

        // Remove non confirmed jobs
        $key = 'txwatch_address:'.$currency.':'.$address;
        $txWatchJobs = Redis::connection()->smembers($key);
        foreach ($txWatchJobs as $idx => $txWatchJobJson) {
            $txWatchJob = json_decode($txWatchJobJson, true);
            //var_dump($jobId);
            if($txWatchJob['jobId'] == $jobId) {
                Redis::connection()->srem($key, $txWatchJobJson);
            }
        }

        foreach (new Keyspace(Redis::connection()->client(), 'txwatch_address_tx_job_id:'.$jobId.':*') as $key) {
            Redis::connection()->del($key);
        }

        return ['done'=>true];
    }

}
