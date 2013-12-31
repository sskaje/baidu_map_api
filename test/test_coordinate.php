<?php
require(__DIR__ . '/../src/BaiduCoordinateConverter.class.php');

$p1 = new spBaiduPointLL(116.301934,39.977552);
var_dump($p1->toMC()->toArray(), $p1->toMC()->toLL()->toArray());