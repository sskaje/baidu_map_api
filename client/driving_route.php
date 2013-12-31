<?php

require(__DIR__ . '/../src/BaiduMap.init.php');

function queryDrvingRoute(array $pairs, $default_city=null, $default_policy=null)
{
	$api_uri = array(
		array(
			'url'	=>	'http://sskaje.sinaapp.com/map/api/driving_route.php',
			'opts'	=>	array(
				'authtype'	=>	CURLAUTH_BASIC,
				'userpwd'	=>	'yidayida:pwpw',
#				'useragent'	=>	'',
			),
			'weight'=>	30,
		),
		array(
			'url'	=>	'http://sskaje.sinaapp.com/map/api/driving_route.php',
			'opts'	=>	array(
				'authtype'	=>	CURLAUTH_BASIC,
				'userpwd'	=>	'yidayida:pwpw',
#				'useragent'	=>	'',
			),
			'weight'=>	30,
		),
	);
	
	$len = 0;
	$array = array();
	foreach ($api_uri as $k=>$v) {
		$array += array_fill($len, $v['weight'], $k);
		$len += $v['weight'];
	}
	$rnd = mt_rand(0, $len-1);
	$key = $array[$rnd];
	
	$api_config = $api_uri[$key];
	
	$json_array = array();
	$json_array['q'] = array();
	foreach ($pairs as $p) {
		if (isset($p['start']) && $p['start'] instanceof spBaiduPoint &&
			isset($p['end']) && $p['end'] instanceof spBaiduPoint
		) {
			$q = array(
				's'	=>	$p['start']->toArray(),
				'e'	=>	$p['end']->toArray(),
			);
			if (isset($p['city'])) {
				$q['c'] = (int) $p['city'];
			}
			if (isset($p['policy'])) {
				$q['p'] = (int) $p['policy'];
			}
			$json_array['q'][] = $q;
		}
	}
	if (!is_null($default_city)) {
		$json_array['c'] = (int) $default_city;
	}
	if (!is_null($default_policy)) {
		$json_array['p'] = (int) $default_policy;
	}
	
	$post_data = array('q'=>json_encode($json_array));
	$http = new spHttpRequest();
	$return = $http->post($api_config['url'], $post_data, $api_config['opts']);
	
	$json = json_decode($return, true);
	
	return $json['data'];
}


$city = 131;
$policy = 0;
$pairs = array(
	array(
		'start'	=>	new spBaiduPointLL(116.409749,39.950409),
		'end'	=>	new spBaiduPointLL(116.470115,39.907584),
	),
	array(
		'start'	=>	new spBaiduPointLL(116.373673,39.968217),
		'end'	=>	new spBaiduPointLL(116.354845,39.888764),
	),
);

$r = queryDrvingRoute($pairs, $city, $policy);
var_dump($r);
# EOF