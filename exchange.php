<?php

use Classes\BitrixSaleExchangeGoldCow;

require __DIR__ . '/vendor/autoload.php';

$bsa = new BitrixSaleExchangeGoldCow('https://zolotoy-telets.ru','admin','iN@YX#IqWePw');
//$bsa = new BitrixSaleExchangeGoldCow('http://localhost','1C-admin','HMM3COJCNy');
$bsa->referencesExchange();
