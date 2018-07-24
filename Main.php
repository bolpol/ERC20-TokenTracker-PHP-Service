<?php
namespace ethparser;

ini_set("max_execution_time", 0);
set_time_limit(0);

use Ethereum\DataType\EthB;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\EthD32;
use Ethereum\Ethereum;
use etherscan\api\Etherscan;
use blacklist\proxy\EtherscanProxy;
use ethparser\model\BalancesIntBalModel;
use ethparser\model\NormalTransactionsModel;
use Web3\Web3;
use ethparser\model\TransactionsModel;

require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/EtherscanProxy.php';
require __DIR__ . '/model/TransactionsModel.php';
require __DIR__ . '/model/NormalTransactionsModel.php';
require __DIR__ . '/model/BalancesIntBalModel.php';

/**
 * Class Main service
 */
class Main
{
    public $web3;
    public $eth_proxy;
    public $eth_api;
    public $infura_api;

    public $contract;
    public $transfer;

    /**
     * Main constructor.
     * @param string $contract
     * @param string $token
     * @param string $transfer_hash - 0x + first 4 bites of transfer functions hash
     */
    public function __construct(string $contract, string $token, string $transfer_hash)
    {
        $this->web3 = new Web3("127.0.0.1");
        $this->auth_token = $token; // SET auth token from etherscan.io
        $this->eth_api = new Etherscan($this->auth_token, null);
        $this->eth_proxy = new EtherscanProxy($this->auth_token);

        try {
            $this->infura_api = new Ethereum('https://mainnet.infura.io/' . INFURAAPI);
        }
        catch (\Exception $exception) {
            die ("Unable to connect.");
        }

        $this->contract = $contract;
        $this->transfer = $transfer_hash;
    }

    public function balancesLength():int
    {
        return count(BalancesIntBalModel::all());
    }

    public function getFirstTransactionBlockNumber($hash)
    {
        $tx = $this->getTransactionByHash($hash);
        return $tx->blockNumber;
    }

    public function getTransactionTimestamp($hash)
    {
        return hexdec($hash->result->timestamp);
    }

    public function getTransactionByHash($hash)
    {
        return $this->infura_api->eth_getTransactionByHash(new EthD32(trim((string) $hash)));
    }

    public function getInputAddress($hex):string
    {
        return "0x" . substr($hex, -40);
    }

    public function getInputAmount($hex, $dec=8):float
    {
        return (hexdec($hex))/(10**$dec);
    }

    public function getInputData($input):array
    {
        $x0 = 2;
        $r1 = substr($input, 0+$x0,  8);
        $r2 = substr($input, 8+$x0,  64);
        $r3 = substr($input, 64+8+$x0, 64+64+8);
        var_dump($r2);
        return [
            $r1,
            (string) "0x" . substr($r2, -40),
            (string) $this->bchexdec($r3)
        ];
    }

    public function writeToTransactions(
        $transaction_hash
        )
    {
        $trx = new TransactionsModel();
        $trx->transaction_hash = $transaction_hash;
        $trx->save();
    }

    public function writeToNormalTransactions(
        $transaction_hash
        )
    {
        $trx = new NormalTransactionsModel();
        $trx->transaction_hash = $transaction_hash;
        $trx->save();
    }

    public function getFistsBlock(tx="")
    {
        $firstTx = 0;

        if(TransactionsModel::all()->last()->block_number !== null){
            $firstTx = TransactionsModel::all()->last()->block_number;

        } else {
            if(tx === "") exit("! pls insert tx hash here");
            $firstTx = $this->getFirstTransactionBlockNumber(tx)->val();
        }

        return $firstTx;

    }

    public function getLatestBlock()
    {
        return $this->infura_api->eth_getBlockByNumber(new EthBlockParam("latest"), new EthB(0))->number->val();
    }

    public function getLimitBlock($block)
    {
        if($this->getTransactionTimestamp($block) >= 1527811200) {
            return true;
        }
    }

    public function getBlock($index)
    {
        return $this->eth_proxy->eth_getBlockByNumber((string) $this->web3->getUtils()->toHex($index, "wei"));
    }
    
    public function blockSearcher()
    {
        //start app
        // Define the block of the first transaction
        $firstTx = $this->getFistsBlock();
        $blocks = $this->getLatestBlock();

        // Run the block recalculation cycle
        for($i = $firstTx; $i<$blocks; $i++) {

            echo "block: " . $i ."<br />";
            var_dump( "block: " . $i);

            $block = $this->eth_proxy->eth_getBlockByNumber((string) $this->web3->getUtils()->toHex($i, "wei"));

            if($block->result === null || $block->result === "NULL") {
                echo "no valid block number or block not exist";
                return;
            }

            $blktxlen = count($block->result->transactions);

            // check all transactions
            for($ii = 0; $ii < $blktxlen; $ii++) { // где ii - номер транзакции

                echo "txs " . $i . "<br />";
                var_dump( "txs " . $i);
                $receipt = $this->eth_proxy->eth_getTransactionReceipt($ii);
                // проверяем статус транзакции если 0x0 то не фейл
                if($receipt->result->status !== "0x0") {

                    // get transaction data
                    $txdata = $this->getInputData($this->getTransactionByHash(
                        $block->result->transactions[$ii]->hash)
                    );

                    // convert this data
                    $txhash = $block->result->transactions[$ii]->from;
                    $from = $block->result->transactions[$ii]->from;
                    $to = $this->getInputAddress($txdata[1]);
                    $amount = floatval($this->getInputAmount ($txdata[2]));
                    $data = $txdata[0]; // 4bytes data of call

                    // один раз за блок..
                    if ($ii == 0) {

                        // ..Check the time condition
                        // Unix timestamp 1527811200 <=> Fri, 01 Jun 2018 00:00:00 GMT
                        if($this->getTransactionTimestamp($block) >= 1527811200) {
                            return;
                        }
                    }

                    // if the transaction was on the contract, then write to the database
                    if($to == CONTRACT) {

                        // if there is no given hash in the table, then skip
                        if((TransactionsModel::where('transaction_hash', $txhash)->get())[0]->exists === null) {
                            $this->writeToTransactions($i, $txhash, $txhash, $to, $amount, $data);
                        }
                    }
                }
            }
        }
    }

    public function writeToBalances($model, $address, $amount):bool
    {
        $db = "\\ethparser\model\\" . "{$model}";
        $brow = $db::where('address', $address)->first();

        // if this address is not present, then we add, if there is then we update the balance
        if(is_null($brow)) {
            $brow = new $db();
            $brow->address = $address;
            $brow->amount = $amount;
            $brow->save();
        } else {
            $brow->amount = $amount;
            $brow->update();
        }
        return true;
    }

    public function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    /**
     * How many it is necessary to take away and add from the addresses of this transaction
     */
    public function countBalances($model, $tx)
    {

        $db = "\\ethparser\model\\" . "{$model}";
        
        if(is_null($tx)) {
            echo "Processing is complete: transactions are out of order or data is not received from the database";
            exit;
        }

        if($tx->timestamp >= 1525132800) {
            echo "Processing completed: By time";
            exit;
        }

                                    //"79ba5097": "acceptOwnership()",
                                    //"dd62ed3e": "allowance(address,address)",
        $approve = "095ea7b3";      //"approve(address,uint256)",
                                    //"70a08231": "balanceOf(address)",
                                    //"313ce567": "decimals()",
        $destroy = "a24835d1";      //"destroy(address,uint256)",
                                    //"1608f18f": "disableTransfers(bool)",
        $issue = "867904b4";        //"issue(address,uint256)",
                                    //"06fdde03": "name()",
                                    //"8da5cb5b": "owner()",
                                    //"95d89b41": "symbol()",
                                    //"18160ddd": "totalSupply()",
        $transfer = "a9059cbb";     // "transfer(address,uint256)",
        $transferFrom = "23b872dd"; // "transferFrom(address,address,uint256)",
                                    //"f2fde38b";"transferOwnership(address)",
                                    //"5e35359e";"withdrawTokens(address,address,uint256)"

        $getInput = $this->getInputData($tx->input);

        if($tx->isError === "0" || is_null($tx->isError)) {

            $sender = $tx->from;
            $spender = $getInput[1];

            $balance_sender = (string) ($db::where('address', $sender)->first())->amount;
            $balance_spender = (string) ($db::where('address', $spender)->first())->amount;

            if($balance_sender === null) $balance_sender = 0;
            if($balance_spender === null) $balance_spender = 0;

            $amount = $getInput[2];

            if($transfer === $getInput[0]) {

                $balance_sender = bcsub((string) $balance_sender, (string) $amount);
                $this->writeToBalances($model, $sender, $balance_sender);

                $balance_spender = bcadd((string) $balance_spender, (string) $amount);
                $this->writeToBalances($model, $spender, $balance_spender);

            }

            if($transferFrom === $getInput[0]) {

                $input = $tx->input;
                $x0 = 2;
                $r1 = substr($input, 0+$x0,  8);
                $r2 = substr($input, 8+$x0,  64);
                $r3 = substr($input, 64+8+$x0,  64);
                $r4 = substr($input, 64+64+8+$x0, 64);

                $getInput = [
                    $r1,
                    (string) "0x" . substr($r2, -40),
                    (string) "0x" . substr($r3, -40),
                    (string) $this->bchexdec($r4)
                ];

                $sender = $getInput[1];
                $spender = $getInput[2];

                $balance_sender = ($db::where('address', $sender)->first())->amount;
                $balance_spender = ($db::where('address', $spender)->first())->amount;

                $amount = $getInput[3];

                $balance_sender = bcsub((string) $balance_sender, (string) $amount);
                $this->writeToBalances($model, $sender, $balance_sender);

                $balance_spender = bcadd((string) $balance_spender, (string) $amount);
                $this->writeToBalances($model, $spender, $balance_spender);

            }

            if($destroy === $getInput[0]) {
                $balance_spender = bcsub((string) $balance_spender, (string) $amount);
                $this->writeToBalances($model, $spender, $balance_spender);
            }

            if($issue === $getInput[0]) {
                $balance_spender = bcadd((string) $balance_spender, (string) $amount);
                $this->writeToBalances($model, $spender, $balance_spender);
            }
        }
    }
}

$app = new Main(CONTRACT, ETHSCNAPI, THASH);

/**
 * Post acceptors bigint data amount
 */
if($_POST["data"]["getcdblen"] === "1") {
    echo $app->balancesLength();
}

/**
 * Post handler for detect move of tokens
 */
if($_POST["data"]["count"]) {
    $txi = (int) $_POST["data"]["count"];

    if(is_null($txi) || $txi === 0) {
        echo "Null or zero incoming";
    } else {
        $tx = NormalTransactionsModel::where("id", $txi)->get();
        $rtx = \json_decode($tx[0]["transaction_hash"]);
        $model = "BalancesIntBalModel";
        echo $app->countBalances($model, $rtx);
    }
}

/**
 * Post for find normal transfers
 */
if($_POST["data"]["findnormaltrxs"] === (int) "1") {
    if(!is_int((int)$_POST["data"]["findnormaltrxs"])) exit("Not int value");
    $len = (int) $_POST["data"]["findnormaltrxs"];
    for($i = 0; $i<$len; $i++) {

        $txs = $app->eth_proxy->get_list_of_normal_txs(
            CONTRACT,
            $i,
            0,
            9999999,
            1,
            "asc"
        );

        if($txs->status !== "1") exit;
        // если не существует данного хеша в таблице, то пропускаем
        $app->writeToNormalTransactions(json_encode($txs->result["0"]));
        time_nanosleep(0, 100000000);
    }
}

/**
 * Post for find ERC20 transfers
 */
if($_POST["data"]["finderc20trxs"] === "1") {

    ($txs = $app->eth_proxy->get_list_of_last10k_erc20_txs(
        CONTRACT,
        "asc"
    ));

    if($txs->status === "1") {

        $len = count($txs->result);
        for($i = 0; $i<$len; $i++) {
            if  (
                $txs->result[$i]->tokenSymbol === TSYMBOL &&
                $txs->result[$i]->tokenName === TNAME
            )
            {
                // получаем данные транзакции
                $txdata = $app->getInputData(
                    $app->getTransactionByHash(
                        $txs->result[$i]->hash
                    )
                );

                $app->writeToTransactions(json_encode($txs->result[$i]));
            }
            time_nanosleep(0, 100000000);
        }
    }
}

