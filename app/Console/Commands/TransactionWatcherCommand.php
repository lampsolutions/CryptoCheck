<?php
namespace App\Console\Commands;

use App\CryptoCurrency;
use App\CryptoCurrencyRate;
use App\Lib\KrakenAPI;
use App\Lib\ProCoinMarketCapApi;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Redis;
use Laravel\Lumen\Http\Request;
use Mockery\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Predis\Collection\Iterator\Keyspace;
use Symfony\Component\Console\Input\InputOption;

class TransactionWatcherCommand extends Command {

    private const TESTBLOCK = '00000000000000000027aef5db80e891d1b1b95f86e3d9b2e9a1d7b8488f4fee';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'cryptocheck:watch {symbol} {--test}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Watches TX-Mempool and Blockchain for specific addresses";
    /**
     * Execute the console command.
     *
     * @return mixed
     */

    protected $testmode = false;

    public function __construct() {

        parent::__construct();
    }

    public function handle() {

        $symbol = $this->argument('symbol');

        if($this->option('test')) {
            $this->testmode = true;
            $block = bitcoind()->client('BTC')->getBlock(self::TESTBLOCK);
            $this->handleBlock('BTC', $block);
            die();
        }

        $this->checkState($symbol);
        $this->handleConfirmedTransactions($symbol);

        bitcoind()->client($symbol)->on('hashblock', function ($blockHash, $sequence) use($symbol) {
            /** @var Response $block */

            $block = bitcoind()->client($symbol)->getBlock($blockHash);
            $this->handleBlock($symbol, $block);

            Redis::connection()->set('current_block_height:'.$symbol, (int)$block['height']);
        });

        bitcoind()->client($symbol)->on('rawtx', function ($txRaw, $size) use($symbol) {
            $txDecodedResponse = bitcoind()->client($symbol)->decoderawtransaction($txRaw);
            $this->handleTx($symbol, $this->getTxByTxHash($symbol, $txDecodedResponse['txid']));
        });

    }

    private function handleBlock($symbol, $block) {
        //print_r($block['height']);
        foreach($block['tx'] as $tx) {
            $this->handleTx($symbol, $this->getTxByTxHash($symbol, $tx));
        }
        $this->handleConfirmedTransactions($symbol);
    }

    private function getTxByTxHash($symbol, $txHash) {
        $txResponse = bitcoind()->client($symbol)->getRawTransaction($txHash, true);
        return $txResponse;
    }



    private function checkState($symbol) {
        $currentBlockHeight = (int)Redis::connection()->get('current_block_height:'.$symbol);

        $info = bitcoind()->client($symbol)->getBlockchainInfo();

        $block = bitcoind()->client($symbol)->getBlock($info['bestblockhash']);

        $latestBlock = $block;

        $this->handleBlock($symbol, $block);

        if($currentBlockHeight == 0 || $currentBlockHeight == $block['height']) {
            // always recheck last 5 blocks
            for ($i = 0; $i < 5; $i++) {
                $block = bitcoind()->client($symbol)->getBlock($block['previousblockhash']);
                $this->handleBlock($symbol, $block);
            }
        } elseif($block['height'] > $currentBlockHeight) {
            while($currentBlockHeight < $block['height']) {
                $block = bitcoind()->client($symbol)->getBlock($block['previousblockhash']);
                $this->handleBlock($symbol, $block);
            }
        }

        Redis::connection()->set('current_block_height:'.$symbol, (int)$latestBlock['height']);
    }

    private function handleTx($symbol, $tx) {
        // Supported types
        // witness_v0_scripthash
        // witness_v0_keyhash
        // pubkey
        // pubkeyhash
        // scripthash

        foreach($tx['vout'] as $vout) {
            if(in_array($vout['scriptPubKey']['type'], ['nulldata'])) continue; // nulldata cannot be spent so not intrested in that


            if(!isset($vout['scriptPubKey']['addresses'])) {
                //print_r($vout);
                echo "Skipping vout part";
            } else {
                foreach($vout['scriptPubKey']['addresses'] as $addr) {
                    $amount = number_format($vout['value'],8);
                    $this->handleTxVout($symbol, $tx, $addr, $amount);
                }
            }
        }
    }

    private function handleTxVout(&$symbol, &$tx, &$addr, $value) {
        $this->log(sprintf('TX %s sends %s %s to address %s', $tx['txid'], $value, $symbol, $addr));

        $txWatchJobs = Redis::connection()->smembers('txwatch_address:'.$symbol.':'.$addr);

        if(!empty($txWatchJobs)) {
            foreach($txWatchJobs as $idx => $txWatchJobJSON) {
                $txWatchJob = json_decode($txWatchJobJSON, true);
                $tx_job_key = 'txwatch_address_tx_job_id:'.$txWatchJob['jobId'].':'.$tx['txid'];

                if($this->isJobConditionFulfilled($tx, $txWatchJob)) {
                    $this->txWatchCompleted($symbol, $tx, $txWatchJob);
                } elseif(empty(Redis::connection()->get($tx_job_key))) {
                    $txWatchJob['tx_id']=$tx['txid'];
                    Redis::connection()->sadd('txwatch_address_confirmed:'.$symbol.':'.$txWatchJob['address'],
                        [ json_encode($txWatchJob) ]);
                    Redis::connection()->set($tx_job_key, "1");
                }
            }
        }

    }


    private function handleConfirmedTransactions($symbol) {
        foreach (new Keyspace(Redis::connection()->client(), 'txwatch_address_confirmed:'.$symbol.':*') as $key) {

            $txWatchJobs = Redis::connection()->smembers($key);
            foreach($txWatchJobs as $idx => $txWatchJobConfirmedJson) {
                $txWatchJobConfirmed = \GuzzleHttp\json_decode($txWatchJobConfirmedJson,true);

                $tx = $this->getTxByTxHash($symbol, $txWatchJobConfirmed['tx_id']);

                if($this->isJobConditionFulfilled($tx, $txWatchJobConfirmed)) {
                    $this->txWatchCompleted($symbol, $tx, $txWatchJobConfirmed);
                }
            }
        }
    }

    private function isJobConditionFulfilled($tx, $job) {
        if((int)$tx['confirmations'] >= $job['confirmations']) {
            return true;
        }

        return false;
    }


    private function txWatchCompleted($symbol, $tx, $txWatchJobConfirmed) {
        $tx_job_done = 'txwatch_done_tx_job_id:'.$txWatchJobConfirmed['jobId'].':'.$tx['txid'];

        if(!$this->testmode && !empty(Redis::connection()->get($tx_job_done))) return;

        Redis::connection()->set($tx_job_done, "1");

        if(!empty($txWatchJobConfirmed['url'])) {
            $total_tx_amount_addr = 0.00000000;
            foreach($tx['vout'] as $vout) {
                if(in_array($vout['scriptPubKey']['type'], ['nulldata'])){
                    continue; // nulldata cannot be spent so not intrested in that
                }

                if(isset($vout['scriptPubKey']['addresses'])) {
                    foreach($vout['scriptPubKey']['addresses'] as $addr) {
                        if($addr == $txWatchJobConfirmed['address']) {
                            $total_tx_amount_addr = number_format($vout['value'],8) + $total_tx_amount_addr;
                        }
                    }
                }
            }

            $payload = \GuzzleHttp\json_encode([
                'symbol' => $symbol,
                'tx' => $tx,
                'job' => $txWatchJobConfirmed,
                'amount' => $total_tx_amount_addr
            ]);

            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->post($txWatchJobConfirmed['url'], ['body' => $payload]);
                if($this->testmode) print_r($response->getBody()->getContents());
            } catch (\Exception $e) {
                $this->log($e->getMessage());
            }

        }
    }

    private function log($message) {
        echo $message . PHP_EOL;
    }

}