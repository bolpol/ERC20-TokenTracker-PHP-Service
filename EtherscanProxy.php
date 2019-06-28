<?php declare(strict_types=1);
/**
 * Created by paulbolhaskih@yahoo.com
 * Author: github.com/gfijrb
 */
namespace blacklist\proxy;

use Ethereum\Ethereum;
use ethparser\Main;


/**
 * Class EtherscanProxy
 * @package EtherscanProxy
 */
class EtherscanProxy {

    private $auth_token;

    /**
     * EtherscanProxy constructor.
     * @param $auth
     */
    function __construct($auth)
    {
        if ($auth == '') {
            return;
        } else {
            $this->auth_token = $auth;
        }
    }

    /**
     * @param string $url
     * @return string
     */
    private function get($url):string
    {
        $data = [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            )
        ];
        $curl = curl_init();
        curl_setopt_array($curl, $data);
        $resp = curl_exec($curl);
        curl_close($curl);
        return $resp;
    }

    /**
     * @link https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_getbalance
     *
     * @param string $contractAddress
     * @param string $walletAddress
     * @return \stdClass
     */
    public function eth_getBalance(string $contractAddress, string $walletAddress):\stdClass
    {
        return \json_decode(
            $this->get(
                'https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=' . $contractAddress .
                '&address=' . $walletAddress .
                '&tag=latest' .
                '&apikey=' . $this->auth_token
            )
        );
    }

    /**
     * @link https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_getblockbynumber
     *
     * @param string $blockNumber
     * @return mixed
     */
    public function eth_getBlockByNumber(string $blockNumber)
    {
        return \json_decode(
            $this->get(
                'https://api.etherscan.io/api?module=proxy&action=eth_getBlockByNumber&tag=' . $blockNumber .
                '&boolean=true&apikey=' . $this->auth_token
            )
        );
    }

    /**
     * @link https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_gettransactionbyhash
     *
     * @param string $hash
     * @return \stdClass
     */
    public function eth_getTransactionByHash(string $hash):\stdClass
    {
        return \json_decode(
            $this->get(
                'https://api.etherscan.io/api?module=proxy&action=eth_getTransactionByHash&txhash=' . $hash .
                '&apikey=' . $this->auth_token
            )
        );
    }

    /**
     * @link https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_gettransactionreceipt
     *
     * @param string $hash
     * @return \stdClass
     */
    public function eth_getTransactionReceipt(string $hash):\stdClass
    {
        return \json_decode(
            $this->get(
                'https://api.etherscan.io/api?module=proxy&action=eth_getTransactionReceipt&txhash=' . $hash .
                '&apikey=' . $this->auth_token
            )
        );
    }

    /**
     * @link https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_gettransactioncount
     *
     * @param string $address
     * @return \stdClass
     */
    public function eth_getTransactionCount(string $address):\stdClass
    {
        return \json_decode(
            $this->get(
                'https://api.etherscan.io/api?module=proxy&action=eth_getTransactionCount&address=' . $address .
                '&tag=latest' .
                '&apikey=' . $this->auth_token
            )
        );
    }

//http://api.etherscan.io/api?module=account&action=txlist&address=0x24eeb54a34d24d4a4baa1b1379928f7978951aca&startblock=0&endblock=99999999&sort=asc&apikey=YourApiKeyToken


    public function getNormalTxs($address, $startblock=0, $endblock=9999999, $sort="asc")
    {
        return \json_decode(
            $this->get(
                'https://api.etherscan.io/api?module=account&action=txlist&address=' . $address .
                '&startblock=' . $startblock .
                '&endblock=' . $endblock .
                '&sort=' . $sort .
                '&apikey=' . $this->auth_token
            )
        );
    }


    public function get_list_of_normal_txs($address, $page=1, $startblock=0, $endblock=9999999, $offset=10, $sort="asc")
    {
        return \json_decode(
            $this->get(
                'https://api.etherscan.io/api?module=account&action=txlist&address=' . $address .
                '&startblock=' . $startblock .
                '&endblock=' . $endblock .
                '&page=' . $page .
                '&offset=' . $offset .
                '&sort=' . $sort .
                '&apikey=' . $this->auth_token
            )
        );
    }

    public function get_list_of_last10k_erc20_txs(string $address, $sort="asc")
    {
        return \json_decode(
            $this->get(
                'http://api.etherscan.io/api?module=account&action=tokentx&address=' .
                $address .
                '&startblock=0&endblock=999999999' .
                '&sort=' . $sort .
                '&apikey=' . $this->auth_token
            )
        );
    }

    public function getTransferEvents(string $address, $sort="asc")
    {
        return \json_decode(
            $this->get(
                'http://api.etherscan.io/api?module=account&action=tokentx&address=' .
                $address .
                '&startblock=0&endblock=999999999' .
                '&sort=' . $sort .
                '&apikey=' . $this->auth_token
            )
        );
    }
}
