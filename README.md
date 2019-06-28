# ERC20-TokenTracker-PHP-Service
token tracker, ethereum token tracker php service, search erc20 transaction

# Start to use

Before using, configure *config.php* file from *config.php.example* (required api infura.io, ethrescan.io)

```$xslt
npm install
cp config.php.example config.php
```

Update project via composer and nodejs.

Set up php and run php server, open CMD and type 
```
php -S localhost:3000
```

# Structure

index.php - simple user interface for init search program

EtherscanProxy.php - updated etherscan api

Main.php - base class

\sql\ - mysql data bases

\model\ - eloquent ORM

# Steps

1) At-first, need find normal transfers. Post query */Main.php* {data: findnormaltrxs [[int] send amount of transactions]}
2) At-second, find by the up data tokens move. Post query */Main.php* {data: count [[int]1] }