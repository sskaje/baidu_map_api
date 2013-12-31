<?php
/**
 * Batch Query API
 *
 * array(
 *     q  => array(
 *         array(
 *             s  => array(longitude, latitude),
 *             e  => array(longitude, latitude),
 *             [c => city code,]
 *             [p => policy,]
 *         )
 *     ),
 *     c  => default city code,
 *     p  => default policy,
 * )
 */


$time0 = microtime(1);
$output = array(
	'code'	=>	0,
	'data'	=>	array(),
);
$AUTH_USER = 'yidayida';
$AUTH_PASS = 'pwpw';
 
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $AUTH_USER || $_SERVER['PHP_AUTH_PW'] != $AUTH_PASS) {
	echo json_encode($output);
	exit;
}

$json = array();
if (isset($_POST['q']) && !empty($_POST['q'])) {
	$json = json_decode($_POST['q'], true);
}

require(__DIR__ . '/../src/BaiduMap.init.php');

$obj = new spBaiduMapApiClient();

$max_queries = 20;

if (!empty($json) && isset($json['q'])) {
	$default_city = 131;
	if (isset($json['c']) && !empty($json['c']) && $obj->isValidCityCode($json['c'])) {
		$default_city = $json['c'];
	}
	
	$default_policy = spBaiduMapApiClient::DRIVING_POLICY_SHORTEST_TIME;
	if (isset($json['p']) && $obj->isValidDrivingPolicy($json['p'])) {
		$default_policy = $json['p'];
	}
	
	$query = array();
	$c = 0;
	foreach ($json['q'] as $k=>$row) {
		if (isset($row['s']) && is_array($row['s']) && isset($row['s'][1]) &&
			isset($row['e']) && is_array($row['e']) && isset($row['e'][1]) 
		) {
			$q = array(
				'start'	=>	array($row['s'][0], $row['s'][1]),
				'end'	=>	array($row['e'][0], $row['e'][1]),
			);
			if (isset($row['c']) && $obj->isValidCityCode($row['c'])) {
				$q['city'] = $row['c'];
			}
			if (isset($row['p']) && $obj->isValidDrivingPolicy($row['p'])) {
				$q['policy'] = $row['p'];
			}
			
			$query[$k] = $q;
			++$c;
			if ($c > $max_queries) {
				break;
			}
		}
	}
	
	$result = $obj->getTaxiDrivingRouteBatch($query, $default_city, $default_policy);
	foreach ($result as $k=>$v) {
		$output['data'][$k] = $v;
	}
}

$output['debug']['exec_time'] = microtime(1) - $time0;
echo json_encode($output);
/*

$result = $obj->getTaxiDrivingRouteByPolicies(
	array(116.301934,39.977552),
	array(116.508328,39.919141),
	131,
	array(0,1,2)
);

var_dump($result);
*/
# EOF
