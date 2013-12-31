<?php
/**
 * Baidu LBS云 客户端
 *
 * @author sskaje
 */
# define('BAIDU_LBSCLOUD_APPKEY', '');
# define('BAIDU_LBSCLOUD_SECRET', '');
 
class spBaiduLBSCloudClient
{
	const TYPE_INT32 = 1;
	const TYPE_INT64 = 2;
	const TYPE_FLOAT = 3;
	const TYPE_DOUBLE= 4;
	const TYPE_STRING= 10;
    
    public function check_type($type)
    {
        $all_types = array(
            self::TYPE_INT32,
            self::TYPE_INT64,
            self::TYPE_FLOAT,
            self::TYPE_DOUBLE,
            self::TYPE_STRING,
        );
        if (in_array($type, $all_types)) {
            return true;
        } else {
            throw new Exception('数据类型错误', 1);
        }
    }
	
	public function filter_page(&$page_index, &$page_size, $max_page_size=50)
	{
		if (!isset($page_index) || $page_index < 0) {
			$page_index = 0;
		} else {
			$page_index = (int) $page_index;
		}
		if (!isset($page_size) || $page_size < 0) {
			$page_size = 10;
		} else if ($page_size > $max_page_size) {
			$page_size = $max_page_size;
		} else {
			$page_size = (int) $page_size;
		}
	}
	const SCOPE_BASIC = 1;
	const SCOPE_EXTEND = 2;

	public function filter_scope(& $scope) 
	{
		if (!isset($scope) || !in_array($scope, array(self::SCOPE_BASIC, self::SCOPE_EXTEND))) {
			$scope = self::SCOPE_BASIC;
		}
	}
	
	protected function buildURL($url)
	{
		if (strpos($url, '?') === false) {
			$c = '?ak=';
		} else {
			$c = '&ak=';
		}
		if (!defined('BAIDU_LBSCLOUD_APPKEY') || !BAIDU_LBSCLOUD_APPKEY) {
			throw new Exception('需要定义 BAIDU_LBSCLOUD_APPKEY', 1);
		}
		$url .= $c . BAIDU_LBSCLOUD_APPKEY;
		return $url;	
	}
	
	public function http_post($url, $params)
	{
		$url = $this->buildURL($url);
		$http = new spHttpRequest();
		$ret = $http->post($url, $params);
        return $this->process_result($ret);
	}
	
	public function http_get($url)
	{
		$url = $this->buildURL($url);
		$http = new spHttpRequest();
		$ret = $http->get($url);
		return $this->process_result($ret);
	}
    
    protected function process_result($result)
    {
        if (!($json = json_decode($result, true))) {
            return false;
        }
        if (!isset($json['status'])) {
            return false;
        }
        
        if ($json['status'] == 0) {
            return $json;
        }
        
        # TODO: ERROR
		return $ret;
    }
	
	const GEO_TYPE_POINT = 1;
	const GEO_TYPE_LINE  = 2;
	const GEO_TYPE_PLANE = 3;
	/**
     * Create DataBox
     *
     * @param string $name
     * @param int    $geotype
     * @return spBaiduLBSCloudDataBox
     */
	public function createDataBox($name, $geotype=1)
	{
		$url = 'http://api.map.baidu.com/geodata/databox?method=create';
		
        $geotype = self::GEO_TYPE_POINT;
        
		$params = array();
		$params['name'] = $name;
		$params['geotype'] = $geotype;
		
		$result = $this->http_post($url, $params);
        if (!isset($result['id']) || !$result['id']) {
            return false;
        }
		# get id
		$databox_id = $result['id'];
		
		return $this->getDataBox($databox_id);
	}
	
	/**
     * search databox
     *
     * @param string $name
     * @param int    $page_index
     * @param int    $page_size
     * @return array
     */
	public function searchDataBox($name='', $page_index=0, $page_size=0)
	{
		$url = 'http://api.map.baidu.com/geodata/databox?method=list';
		
		$this->filter_page($page_index, $page_size);
        
        $params = array();
        $params['name'] = $name;
        $params['page_index'] = $page_index;
        $params['page_size'] = $page_size;
        
		$result = $this->http_post($url, $params);
        
        if (!$result['size']) {
            return array();
        } else {
            $ret = array();
            foreach ($result['databox'] as $data) {
                $ret[] = new spBaiduLBSCloudDataBox($data['id'], $data['name'], $data['geotype'], $data['create_time']);
            }
            return $ret;
        }
	}
	/**
     * get databox object
     *
     * @return spBaiduLBSCloudDataBox
     */
	public function getDataBox($databox_id)
	{
		return new spBaiduLBSCloudDataBox($databox_id);
	}
}
/**
 * Data Box Class
 *
 */
class spBaiduLBSCloudDataBox extends spBaiduLBSCloudClient
{		
	protected $databox_id;
    protected $name;
    protected $geotype;
    protected $create_time;
    
	protected function __construct($databox_id, $name='', $geotype='', $create_time='')
	{
		$this->databox_id = $databox_id;
	}
	/**
     * Change DataBox Name
     *
     * @param string $name
     * @return bool
     */
	public function update($name)
	{
		$url = "http://api.map.baidu.com/geodata/databox/{$this->databox_id}?method=update";
		$params = array(
            'name'  =>  $name,
        );
        $result = $this->http_post($url, $params);
        return $reult['status'] == 0 ? true : false;
	}
	/**
     * Delete DataBox
     *
     * @return bool
     */
	public function delete()
	{
		$url = "http://api.map.baidu.com/geodata/databox/{$this->databox_id}?method=delete";
		$params = array();
        $result = $this->http_post($url, $params);
        return $reult['status'] == 0 ? true : false;
	}
    
    public function __get($key)
    {
        $data = $this->detail();
        return isset($data[$key]) ? $data[$key] : null;
    }
    public function __isset($key)
	{
        $data = $this->detail();
        return isset($data[$key]);
    }
    public function __set($key, $val)
    {
        # DO NOTHING
    }
    public function __unset($key)
    {
        # DO NOTHING
    }
    /**
     * detail of data box
     *
     * @return array array(id=>int, name=>string, geotype=int, create_time=int)
     */
	public function detail()
	{
        if ($this->name) {
            return array(
                'id'            =>  $this->databox_id,
                'name'          =>  $this->name,
                'geotype'       =>  $this->geotype,
                'create_time'   =>  $this->create_time,
            );
        } else {
            $url = "http://api.map.baidu.com/geodata/databox/{$this->databox_id}";
            $ret = $this->http_get($url);
            
            $this->name         = $ret['databox']['name'];
            $this->geotype      = $ret['databox']['geotype'];
            $this->create_time  = $ret['databox']['create_time'];
            $this->databox_id   = $ret['databox']['id'];
            
            return $this->detail();
        }
	}
	
	public function createMeta($property_name, $property_key, $property_type, $if_magic_field=true)
	{
        $this->check_type($property_type);
        
		$url = 'http://api.map.baidu.com/geodata/databoxmeta?method=create';
        
		$params = array();
        $params['property_name'] = $property_name;
        $params['property_key']  = $property_key;
        $params['property_type'] = $property_type;
        $params['if_magic_field'] = $if_magic_field;
        
        $result = $this->http_post($url, $params);
	}

	public function searchMeta($property_name, $property_key)
	{
		$url = 'http://api.map.baidu.com/geodata/databoxmeta?method=list';
        
	}
    
    public function getMeta($databox_meta_id)
    {
        
    }
	
	public function createPOI()
	{
		
	}
	
	public function deletePOI($ids)
	{
		
	}
	
	public function searchPOI()
	{
		
	}
}

class spBaiduLBSCloudDataBoxMeta extends spBaiduLBSCloudClient
{
	protected $databox;
	protected $databox_meta_id;
	
	public function __construct($databox_meta_id, spBaiduLBSCloudDataBox & $databox)
	{
		$this->databox = $databox;
        $this->databox_meta_id = $databox_meta_id;
	}
	
	public function update($property_name, $if_magic_field)
	{
		$url = 'http://api.map.baidu.com/geodata/databoxmeta/{$this->databox_meta_id}?method=update';
	}
	
	public function detail()
	{
		$url = 'http://api.map.baidu.com/geodata/databoxmeta/{$this->databox_meta_id}';
	}
	
	static public function Get($databox_meta_id)
	{
		
	}
}


class spBaiduLBSCloudPOI extends spBaiduLBSCloudClient
{
	public function __construct()
	{
		
	}	
	public function update()
	{
		
	}
	
	public function delete()
	{
		
	}
	
	public function detail()
	{
	}
	
	public function createExt()
	{
		
	}
	
	public function deleteExt()
	{
		
	}
	
	public function updateExt()
	{
		
	}
	
	public function upload()
	{
		
	}
}


class spBaiduLBSCloudClientS
{

	public function searchRegion($q, $tag, $region, $filter, $scope=0, $page_index=0, $page_size=10, $callback='')
	{
		$this->filter_scope($scope);
		$this->filter_page($page_index, $page_size);
		
	
		$url = 'http://api.map.baidu.com/geosearch/poi';
		$params = array();
		$must = array('filter');
		$optional = array('q', 'tag', 'region', 'scope', 'page_index', 'page_size', 'callback');
		
		foreach ($optional as $p) {		
			if ($$p) {
				$params[$p] = $$p;
			}	
		}
		
		foreach ($must as $p) {
			$params[$p] = $$p;
		}
		
		# Build URL
		# Execute Post
		# Process Response
	}
	
	public function searchNearBy()
	{
		
	}
	
	public function searchBounds()
	{
		
	}
	
	public function searchDetail()
	{
		
	}
	
	
	
	public function databoxCreate()
	{
		
	}
	
	public function databoxUpdate()
	{
		
	}
	
	public function databoxDelete()
	{
		
	}
	
	public function databoxGet()
	{
		
	}
	
	public function databoxList()
	{
		
	}
	
	
}
