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


$eth = new EtherscanProxy(ETHSCNAPI);

var_dump($eth->getNormalTxs(CONTRACT));