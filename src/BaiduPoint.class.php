<?php
/**
 * Baidu地图用坐标转换工具
 * 适用于 Baidu地图 Web版接口调用、Baidu地图 JS SDK接口调用。
 * 		
 * @author sskaje
 * @link http://api.map.baidu.com/getscript?v=1.3&key=&services=&t=20121127154746
 */
 
/**
 * 百度坐标点基类
 *
 */
abstract class spBaiduPoint
{
	public $lng;
	public $lat;
	public function __construct($lng, $lat)
	{
		
	}
	/**
	 * 坐标转数组
	 * [0]->longitude, [1]->latitude
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array($this->lng, $this->lat);
	}
	/**
	 * 转成文本输出
	 *
	 * @return string  lng,lat
	 */
	public function toText()
	{
		return implode(',', $this->toArray());
	}
}
/**
 * 百度地图坐标点类 标准经纬度
 * 
 */
class spBaiduPointLL extends spBaiduPoint
{
	const LONGITUDE_MAX = 180;
	const LONGITUDE_MIN = -180;
	const LATITUDE_MAX = 74;
	const LATITUDE_MIN = -74;

	public function __construct($lng, $lat)
	{
		$this->lng = $this->filterLongitude($lng);
		$this->lat = $this->filterLatitude($lat);
	}
	
	public function getRadians()
	{
		return array(
			spBaiduCoordinateConverter::toRadians($this->lng),
			spBaiduCoordinateConverter::toRadians($this->lat),
		);
	}
	
	static public function filterLatitude($lat) 
	{
		$lat = max($lat, self::LATITUDE_MIN);
		$lat = min($lat, self::LATITUDE_MAX);
		return $lat;
	}
	static public function filterLongitude($lng) 
	{
		while ($lng > self::LONGITUDE_MAX) {
			$lng -= self::LONGITUDE_MAX - self::LONGITUDE_MIN;
		}
		while ($lng < self::LONGITUDE_MIN) {
			$lng += self::LONGITUDE_MAX - self::LONGITUDE_MIN;
		}
		return $lng;
	}
	/**
	 * 转 MC 坐标
	 * @return spBaiduPointMC
	 */
	public function toMC()
	{
		return spBaiduCoordinateConverter::convertLL2MC($this);
	}
}

/**
 * 百度地图坐标点类 墨卡托经纬度
 * 
 * 
 */
class spBaiduPointMC extends spBaiduPoint
{
	public function __construct($lng, $lat)
	{
		$this->lng = $lng;
		$this->lat = $lat;
	}
	/**
	 * 转 标准经纬坐标
	 * @return spBaiduPointMC
	 */
	public function toLL()
	{
		return spBaiduCoordinateConverter::convertMC2LL($this);
	}	
}
/**
 * 百度地图坐标点转换工具类
 * 经纬坐标和墨卡托互转
 *
 */
class spBaiduCoordinateConverter
{
	static public $EARTHRADIUS = 6370996.81;
	static public $MCBAND = array(
		12890594.86, 8362377.87, 5591021, 3481989.83, 1678043.12, 0
	);
	static public $LLBAND = array(
		75, 60, 45, 30, 15, 0
	);
	static public $MC2LL = array(
		array(1.410526172116255e-8, 0.00000898305509648872, -1.9939833816331, 200.9824383106796, -187.2403703815547, 91.6087516669843, -23.38765649603339, 2.57121317296198, -0.03801003308653, 17337981.2),
		array(-7.435856389565537e-9, 0.000008983055097726239, -0.78625201886289, 96.32687599759846, -1.85204757529826, -59.36935905485877, 47.40033549296737, -16.50741931063887, 2.28786674699375, 10260144.86),
		array(-3.030883460898826e-8, 0.00000898305509983578, 0.30071316287616, 59.74293618442277, 7.357984074871, -25.38371002664745, 13.45380521110908, -3.29883767235584, 0.32710905363475, 6856817.37),
		array(-1.981981304930552e-8, 0.000008983055099779535, 0.03278182852591, 40.31678527705744, 0.65659298677277, -4.44255534477492, 0.85341911805263, 0.12923347998204, -0.04625736007561, 4482777.06),
		array(3.09191371068437e-9, 0.000008983055096812155, 0.00006995724062, 23.10934304144901, -0.00023663490511, -0.6321817810242, -0.00663494467273, 0.03430082397953, -0.00466043876332, 2555164.4),
		array(2.890871144776878e-9, 0.000008983055095805407, -3.068298e-8, 7.47137025468032, -0.00000353937994, -0.02145144861037, -0.00001234426596, 0.00010322952773, -0.00000323890364, 826088.5)
	);
	static public $LL2MC = array(
		array(-0.0015702102444, 111320.7020616939, 1704480524535203, -10338987376042340, 26112667856603880, -35149669176653700, 26595700718403920, -10725012454188240, 1800819912950474, 82.5),
		array(0.0008277824516172526, 111320.7020463578, 647795574.6671607, -4082003173.641316, 10774905663.51142, -15171875531.51559, 12053065338.62167, -5124939663.577472, 913311935.9512032, 67.5),
		array(0.00337398766765, 111320.7020202162, 4481351.045890365, -23393751.19931662, 79682215.47186455, -115964993.2797253, 97236711.15602145, -43661946.33752821, 8477230.501135234, 52.5),
		array(0.00220636496208, 111320.7020209128, 51751.86112841131, 3796837.749470245, 992013.7397791013, -1221952.21711287, 1340652.697009075, -620943.6990984312, 144416.9293806241, 37.5),
		array(-0.0003441963504368392, 111320.7020576856, 278.2353980772752, 2485758.690035394, 6070.750963243378, 54821.18345352118, 9540.606633304236, -2710.55326746645, 1405.483844121726, 22.5),
		array(-0.0003218135878613132, 111320.7020701615, 0.00369383431289, 823725.6402795718, 0.46104986909093, 2351.343141331292, 1.58060784298199, 8.77738589078284, 0.37238884252424, 7.45)
	);
	
	static public function toRadians($T) 
	{
		return M_PI * $T / 180;
	}
	static public function toDegrees($T) 
	{
		return (180 * $T) / M_PI;
	}
	static public function getDistance(spBaiduPointLL $p1, spBaiduPointLL $p2)
	{
		$p1r = $p1->getRadians();
		$p2r = $p2->getRadians();
		return self::$EARTHRADIUS * acos((sin($p1r[1]) * sin($p2r[1]) + cos($p1r[1]) * cos($p2r[1]) * cos($p2r[0] - $p1r[0])));
	}
	
	static public function getDistanceByMC(spBaiduPointMC $p1, spBaiduPointMC $p2) 
	{
		return self::getDistance($p1->toLL(), $p2->toLL());
	}
	static public function getDistanceByLL(spBaiduPointLL $p1, spBaiduPointLL $p2) 
	{
		return this.getDistance($p1, $p2);
	}
	static public function convertMC2LL(spBaiduPointMC $point) 
	{
		$np = new spBaiduPointMC(abs($point->lng), abs($point->lat));
		$cM = array();
		for ($cL = 0; $cL < count(self::$MCBAND); $cL++) {
			if ($np->lat >= self::$MCBAND[$cL]) {
				$cM = self::$MC2LL[$cL];
				break;
			}
		}
		
		$T = self::convertor($np, $cM);
		return new spBaiduPointLL(round($T[0], 6), round($T[1],6));
	}
	static public function convertLL2MC(spBaiduPointLL $point) 
	{
		$cL = array();
		for ($cK = 0; $cK < count(self::$LLBAND); $cK++) {
			if ($point->lat >= self::$LLBAND[$cK]) {
				$cL = self::$LL2MC[$cK];
				break;
			}
		}
		
		if (!$cL) {
			for ($cK = count(self::$LLBAND) - 1; $cK >= 0; $cK--) {
				if ($point->lat <= -self::$LLBAND[$cK]) {
					$cL = self::$LL2MC[$cK];
					break;
				}
			}
		}
		$cM = self::convertor($point, $cL);
		return new spBaiduPointMC(round($cM[0], 2), round($cM[1], 2));
	}
	
	static public function convertor(spBaiduPoint $cK, array $cL) 
	{
		$T = $cL[0] + $cL[1] * abs($cK->lng);
		$cJ = abs($cK->lat) / $cL[9];
		$cM = $cL[2] + $cL[3] * $cJ + $cL[4] * $cJ * $cJ + $cL[5] * $cJ * $cJ * $cJ + $cL[6] * $cJ * $cJ * $cJ * $cJ + $cL[7] * $cJ * $cJ * $cJ * $cJ * $cJ + $cL[8] * $cJ * $cJ * $cJ * $cJ * $cJ * $cJ;
		$T *= ($cK->lng < 0 ? -1 : 1);
		$cM *= ($cK->lat < 0 ? -1 : 1);
		return array($T, $cM);
	}
}

# EOF