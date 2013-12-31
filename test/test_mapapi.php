<?php
require(__DIR__ . '/../src/BaiduMap.init.php');


$obj = new spBaiduMapApiClient();
/*
$result = $obj->getTaxiDrivingRoute(
	array(116.301934,39.977552),
	array(116.508328,39.919141),
	131,
	0
);

var_dump($result);
*/



$result = $obj->getTaxiDrivingRouteByPolicies(
	array(116.301934,39.977552),
	array(116.508328,39.919141),
	131,
	array(0,1,2)
);

var_dump($result);