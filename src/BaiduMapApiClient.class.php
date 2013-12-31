<?php
/**
 * Baidu地图 Web版 API接口
 * 
 * @author sskaje
 */
class spBaiduMapApiClient
{
	const DRIVING_POLICY_SHORTEST_DIST = 0;
	const DRIVING_POLICY_SHORTEST_TIME = 1;
	const DRIVING_POLICY_BYPASS_HIGHWAY = 2;
	/**
	 * 全部策略
	 *
	 * @var array
	 */
	public function getAllPolicies()
	{
		return array(
			self::DRIVING_POLICY_SHORTEST_DIST,
			self::DRIVING_POLICY_SHORTEST_TIME,
			self::DRIVING_POLICY_BYPASS_HIGHWAY,
		);
	}
	/**
	 * 判断是否为合法的策略
	 *
	 * @param int $p
	 * @return boolean
	 */
	public function isValidDrivingPolicy($p)
	{
		return in_array($p, $this->getAllPolicies());
	}
	/**
	 * 可用的城市列表
	 *
	 * @var array
	 */
	static $city_code = array(
		'131'	=>	'北京',
		'289'	=>	'上海',
		'257'	=>	'广州',
		'340'	=>	'深圳',
		'75'	=>	'成都',
		'332'	=>	'天津',
		'315'	=>	'南京',
		'179'	=>	'杭州',
		'218'	=>	'武汉',
		'132'	=>	'重庆',
	);
	/**
	 * 判断是否为合法的城市编码
	 *
	 * @param int $c
	 * @return boolean
	 */
	public function isValidCityCode($c)
	{
		return isset(self::$city_code[$c]);
	}
	/**
	 * 单一策略出租驾驶路径
	 *
	 * @param array $start array(0=>longitude, 1=>latitude)
	 * @param array $end   array(0=>longitude, 1=>latitude)
	 * @param int   $city  city code
	 * @param int   $policy
	 * @return array json
	 */
	public function getTaxiDrivingRoute(array $start, array $end, $city, $policy=0)
	{
		$http = new spHttpRequest();
		$return = $http->get($this->createURL($start, $end, $city, $policy));
		
		# Process Result
		if (($json = json_decode($return, true))) {
			return $json['content']['taxi'];
		} else {
			return false;
		}
	}
	
	/**
	 * 多个策略同时查出租驾驶路径
	 *
	 * @param array $start array(0=>longitude, 1=>latitude)
	 * @param array $end   array(0=>longitude, 1=>latitude)
	 * @param int   $city  city code
	 * @param array $policy
	 * @return array json
	 */
	public function getTaxiDrivingRouteByPolicies(array $start, array $end, $city, $policies=array())
	{
		$all_policies = $this->getAllPolicies();
		if (empty($policies)) {
			$policies = $all_policies;
		}
		$pairs = array();
		foreach ($policies as $k=>$p) {
			if (in_array($p, $all_policies)) {
				$pairs[$p] = array(
					'start'	=>	$start, 
					'end'	=>	$end, 
#					'city'	=>	$city, 
					'policy'=>	$p
				);
			}
		}
		
		return $this->getTaxiDrivingRouteBatch($pairs, $city);
	}
	
	/**
	 * 创建出租驾驶路径查询url
	 *
	 * @param array $start array(0=>longitude, 1=>latitude)
	 * @param array $end   array(0=>longitude, 1=>latitude)
	 * @param int   $city  city code
	 * @param int   $policy
	 * @return array json
	 */
	public function createURL(array $start, array $end, $city=131, $policy=0)
	{
		$url = 'http://api.map.baidu.com/';
		$query = array();
		$query['qt'] = 'nav';
		$query['ie'] = 'utf-8';
		$query['oue'] = '1';
		
		$query['c'] = $city;
		$query['sy'] = $policy;
		$query['sn'] = $this->buildQueryPoint($start[0], $start[1]);
		$query['en'] = $this->buildQueryPoint($end[0], $end[1]);
		
		return $url . '?' . http_build_query($query);
	}
	/**
	 * 批量查询出租驾驶路径
	 *
	 * @param array $pair   array(start=>array(0=>longitude, 1=>latitude),end=>array(0=>longitude, 1=>latitude)[,city=>int city code][,policy=>int policy']);
	 * @param int   $city  city code
	 * @param array $policy
	 * @return array json
	 */
	public function getTaxiDrivingRouteBatch(array $pairs, $city=131, $policy=0) 
	{
		$urls = array();
		$c = 0;
		foreach ($pairs as $k=>$p) {
			$urls[$k] = $this->createURL(
				$p['start'], 
				$p['end'], 
				isset($p['city']) ? $p['city'] : $city, 
				isset($p['policy']) ? $p['policy'] : $policy
			);
			++$c;
		}
		$http = new spHttpRequest();
		$result = $http->get($urls, array('connecttimeout'=>$c+1));
		
		$ret = array();
		foreach ($result as $k=>$v) {
			$json = json_decode($v, true);
			$ret[$k] = $json['content']['taxi'];
		}
		return $ret;
	}
	/**
	 * 创建百度地图MC坐标的查询参数
	 * 
	 * @param double $longitude
	 * @param double $latitude
	 * @return string 
	 */
	public function buildQueryPoint($longitude, $latitude)
	{
		$point = new spBaiduPointLL($longitude, $latitude);
		$geo = $point->toMC()->toText();
		return '1$$$$'.$geo.'$$$$$$';
	}
	/**
	 * 调用Baidu接口转 WGS84 为Baidu坐标
	 *
	 * @param spBaiduPoint $point
	 * @return array
	 */
	public function GPS2Baidu(spBaiduPoint $point)
	{
		return $this->convertCoordinate(0, 4, array($point));
	}
	/**
	 * 调用Baidu接口转 Google/Sogou/Soso 为Baidu坐标
	 * Google 坐标需要反转经纬度
	 * 
	 * @param spBaiduPoint $point
	 * @return array
	 */
	public function GCJ2Baidu(spBaiduPoint $point)
	{
		return $this->convertCoordinate(2, 4, array($point));
	}
	/**
	 * MapBar坐标转WGS84
	 *
	 * @param float $x
	 * @param float $y
	 * @return array
	 */
	public function Mapbar2WGS84($x, $y)
	{
		$x = $x * 100000 % 36000000;
		$y = $y * 100000 % 36000000;
		
		$x1 = intval(-(((cos($y/100000)) * ($x/18000)) + ((sin($x/10000)) * ($y/9000))) + $x);
		$y1 = intval(-(((sin($y/100000)) * ($x/18000)) + ((cos($x/10000)) * ($y/9000))) + $y);
		$x2 = intval(-(((cos($y1/100000)) * ($x1/18000)) + ((sin($x1/10000)) * ($y1/9000))) + $x + (($x>0) ? 1 : -1));
		$y2 = intval(-(((sin($y1/100000)) * ($x1/18000)) + ((cos($x1/10000)) * ($y1/9000))) + $y + (($y>0) ? 1 : -1));
		
		return array(
			$x2 / 100000,
			$y2 / 100000,
		);
	}
	
	static public $coordinate_system = array(
		0	=>	'wgs84ll',	# WGS84经纬度
		1	=>	'wgs84mc',	# WGS84墨卡托
		2	=>	'gcj02ll',	# 国测局加密经纬度
		3	=>	'gcj02mc',	# 国测局加密墨卡托
		4	=>	'bd09ll',	# 百度加密经纬度
		5	=>	'bd09mc',	# 百度加密墨卡托
	);
	/**
	 * Baidu坐标系转换接口
	 *
	 * @param int $from
	 * @param int $to
	 * @param array $coordinates
	 * @return array
	 */
	public function convertCoordinate($from, $to, array $coordinates=array())
	{
		if (!isset(self::$coordinate_system[$from]) || !isset(self::$coordinate_system[$to])) {
			return false;
		}
		
		$max_coordinates = 20;
		
		$longitude = $latitude = $comma = '';
		$c = 0;
		foreach ($coordinates as $c) {
			$longitude .= $comma . $c->lng;
			$latitude  .= $comma . $c->lat;
			$comma = ',';
			++$c;
			if ($c >= $max_coordinates) {
				break;
			}
		}
		
		$url = 'http://api.map.baidu.com/ag/coord/convert?from='.$from.'&to='.$to.'&x='.$longitude.'&y='.$latitude;
		if ($c > 1) {
			$url .= '&mode=1';
		}
		
		$http = new spHttpRequest();
		$result = $http->get($url);
		if (($json = json_decode($result, true))) {
			if (isset($json['error']) && $json['error'] == 0) {
				return array(
					base64_decode($json['x']),
					base64_decode($json['y']),
				);
			} else if (isset($json[0]['error'])) {
				$ret = array();
				foreach ($json as $j) {
					if ($j['error'] == 0) {
						$ret[] = array(
							base64_decode($j['x']),
							base64_decode($j['y']),
						);	
					} else {
						$ret[] = array();
					}
				}
				return $ret;
			}
		}
		return false;
	}
}

# EOF
