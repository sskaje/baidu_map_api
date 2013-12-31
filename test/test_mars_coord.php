<?php
require(__DIR__ . '/../src/BaiduMap.init.php');


$obj = new spBaiduMapApiClient();

/*
$result = $obj->GPS2Baidu(new spBaiduPointLL(116.301934,39.977552));
var_dump($result);
$result = $obj->GPS2Baidu(new spBaiduPointLL(116.508328,39.919141));
var_dump($result);
 */
$result = $obj->GPS2Baidu(new spBaiduPointLL(116.36108,40.09264));
var_dump($result);
