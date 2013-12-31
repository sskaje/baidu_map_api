<?php
/**
 * http request component
 *
 * @author sskaje (kemeng@staff, sskaje@gmail.com)
 */
if (!extension_loaded('curl')) {
    die("模块 <strong>curl</strong> 未加载");
}
class spHttpRequest
{
    /**
     * 处理 http get
     *
     * @param string|array			$url			可以传入字符串或url数组
     * @param array					$options		array(CULROPT_*=>val, CURLOPT_*=>val)
     * @param boolean				$force_curl		仅当传入url为字符串时，若 $options 为空，默认使用file_get_contents() 取数据，可以强制指定使用 curl.
     * @return string|array
     */
    public function get($url, $options = array(), $force_curl = 0)
    {
        # 单一请求
        if (!is_array($url)) {
            # 默认使用 file_get_contents() 取数据
            if (empty($options) && ! $force_curl) {
                if (! $this->_checkUrl($url)) {
                    return false;
                } else {
                	# TODO: timeout
                    return file_get_contents($url);
                }
            } else {
                $ch = curl_init();
                $this->initCurl($ch);
                $options = $this->_processCurlOptions(
                	$options,
                    array(
                        CURLOPT_POST, 
                        CURLOPT_POSTFIELDS, 
                        CURLOPT_NOBODY, 
                        CURLOPT_CUSTOMREQUEST
                    ), 
                    true
                );
                $options[CURLOPT_URL] = $url;
                # Force to HTTP GET
                $options[CURLOPT_HTTPGET] = true;
                curl_setopt_array($ch, $options);
                return curl_exec($ch);
            }
        } else {
            # 处理多请求
            $mh = curl_multi_init();
            $chs = array();
            $options = $this->_processCurlOptions(
            	$options,
                array(
                    CURLOPT_POST, 
                    CURLOPT_POSTFIELDS, 
                    CURLOPT_NOBODY, 
                    CURLOPT_CUSTOMREQUEST
                ), 
                true
            );
            # Force to HTTP GET
            $options[CURLOPT_HTTPGET] = true;
            foreach ($url as $_k => $_u) {
                $chs[$_k] = curl_init();
                $this->initCurl($chs[$_k]);
                $options[CURLOPT_URL] = $_u;
                curl_setopt_array($chs[$_k], $options);
                curl_multi_add_handle($mh, $chs[$_k]);
            }
            # code below are from php manual
            $active = null;
            //execute the handles
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            
            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) != - 1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }
            # code above are from php manual
            $ret = array();
            foreach ($chs as $_k => $ch) {
                $ret[$_k] = curl_multi_getcontent($chs[$_k]);
                curl_multi_remove_handle($mh, $chs[$_k]);
            }
            curl_multi_close($mh);
            unset($chs);
            return $ret;
        }
    }

    /**
     * HTTP POST
     *
     * @param string			$url
     * @param string|array		$data
     * @param array				$options
     * @return string
     */
    public function post($url, $data, $options = array(), &$info=null)
    {
        $ch = curl_init();
        $this->initCurl($ch);
        $options = $this->_processCurlOptions(
        	$options, 
        	array(
	            CURLOPT_NOBODY, 
	            CURLOPT_HTTPGET, 
	            CURLOPT_CUSTOMREQUEST
        	), 
        	true
        );
        $options[CURLOPT_POST] = 1;
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_POSTFIELDS] = $data;
        curl_setopt_array($ch, $options);
        $ret = curl_exec($ch);
        
        if (isset($info) && $info!==null) {
        	$info = curl_getinfo($ch);
        }
        
        return $ret;
    }

    /**
     * HTTP HEAD
     *
     * @param string			$url
     * @param array				$options
     * @return array
     */
    public function head($url, $options = array())
    {
        $ch = curl_init();
        $this->initCurl($ch);
        $options = $this->_processCurlOptions(
        	$options,
            array(
                CURLOPT_POST, 
                CURLOPT_POSTFIELDS, 
                CURLOPT_HTTPGET, 
                CURLOPT_CUSTOMREQUEST
            ), 
            true
        );
        $options[CURLOPT_NOBODY] = 1;
        $options[CURLOPT_URL] = $url;
        curl_setopt_array($ch, $options);
        # Force to HTTP HEAD
        curl_exec($ch);
        return curl_getinfo($ch);
    }

    /**
     * HTTP DELETE
     *
     * @param string			$url
     * @param array				$options
     * @return array
     */
    public function delete($url, $data, $options = array())
    {
        $ch = curl_init();
        $this->initCurl($ch);
        $options = $this->_processCurlOptions(
        	$options, 
        	array(
            	CURLOPT_POST, 
            	CURLOPT_HTTPGET, 
            	CURLOPT_NOBODY
            ), 
            true
        );
        # Customize header to HTTP DELETE
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        $options[CURLOPT_URL] = $url;
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        return curl_getinfo($ch);
    }

    /**
     * 检查url格式是否合法
     * 仅用于file_get_contents() 被调用前
     *
     * @param string			$url
     */
    protected function _checkUrl($url)
    {
        # 检查 :/ 简单限制不能为文件系统路径
        if (!strpos($url, ':/')) {
            return false;
        } else {
            return $url;
        }
    }

    /**
     * 初始化curl选项
     *
     * @param resource 			& $ch			curl 资源
     */
    public function initCurl(& $ch)
    {
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    }

    /**
     * 处理 options
     *
     * @param array				$options		选项，可以使用 timeout, referer, cookie 等，也可以使用 CURLOPT_*
     * @param array				$optionlist		选项名单 array(CURLOPT_*)
     * @param array				$blacklist		选项类型，$optionlist 为黑名单或白名单
     * @return array
     */
    protected function _processCurlOptions($options, $optionlist = array(), $blacklist = true)
    {
        $option_map = array(
            'timeout' => CURLOPT_TIMEOUT,
            'connecttimeout' => CURLOPT_CONNECTTIMEOUT,
#            'connecttimeout_ms' => CURLOPT_CONNECTTIMEOUT_MS,
            'referer' => CURLOPT_REFERER,
            'cookie' => CURLOPT_COOKIE,
            'cookiejar' => CURLOPT_COOKIEJAR,
            'cookiefile' => CURLOPT_COOKIEFILE,
            'version' => CURLOPT_HTTP_VERSION,
            'data' => CURLOPT_POSTFIELDS,
            'post' => CURLOPT_POST,
            'get' => CURLOPT_HTTPGET,
            'authtype' => CURLOPT_HTTPAUTH,
            'userpwd' => CURLOPT_USERPWD,
            'useragent' => CURLOPT_USERAGENT
        );
        foreach ($option_map as $k => $v) {
            if (isset($options[$k])) {
                $options[$v] = $options[$k];
                unset($options[$k]);
            }
        }
        if (! empty($optionlist)) {
            if ($blacklist) {
                foreach ($optionlist as $v) {
                    if (isset($options[$v])) {
                        unset($options[$v]);
                    }
                }
            } else {
                foreach ($options as $k => $v) {
                    if (! in_array($k, $optionlist)) {
                        unset($options[$k]);
                    }
                }
            }
        }
        if (isset($options[CURLOPT_HTTP_VERSION])) {
            if (! in_array($options[CURLOPT_HTTP_VERSION],
                array(
                    CURL_HTTP_VERSION_1_0, CURL_HTTP_VERSION_1_1, CURL_HTTP_VERSION_NONE
                ))) {
                if ($options[CURLOPT_HTTP_VERSION] == '1.0') {
                    $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
                } else if ($options[CURLOPT_HTTP_VERSION] == '1.1') {
                    $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
                } else {
                    $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_NONE;
                }
            }
        }
        return $options;
    }

    #################################################
    #
    #		多任务处理
    #
    #################################################
    /**
     * 多任务信息
     *
     * @var					array(0=>array(0=>& curl_res, 1=>options), 1=> ...)
     */
    protected $_multitasks = array();

    /**
     * curl_multi_init 资源
     *
     * @var					curl_res
     */
    protected $_multitasks_handler = null;

    /**
     * 初始化 多任务
     */
    public function initMultiTask()
    {
        $this->_multitasks_handler = curl_multi_init();
    }

    /**
     * 添加任务
     *
     * @param string 		$httpmethod			get/post/delete/head
     * @param string		$url				url
     * @param array			$options			array options
     * @param string		$key				key参数
     * @return boolean
     */
    public function AddTask($httpmethod, $url, $options = array(), $key = null)
    {
        $ch = curl_init();
        $this->initCurl($ch);
        switch (strtolower($httpmethod)) {
        case 'get':
            $options = $this->_processCurlOptions($options,
                array(
                    CURLOPT_POST, CURLOPT_POSTFIELDS, CURLOPT_NOBODY, CURLOPT_CUSTOMREQUEST
                ), true);
            $options[CURLOPT_HTTPGET] = true;
            $options[CURLOPT_URL] = $url;
            break;
        case 'post':
            $options = $this->_processCurlOptions($options,
                array(
                    CURLOPT_NOBODY, CURLOPT_HTTPGET, CURLOPT_CUSTOMREQUEST
                ), true);
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_URL] = $url;
            break;
        case 'head':
            $options = $this->_processCurlOptions($options,
                array(
                    CURLOPT_POST, CURLOPT_POSTFIELDS, CURLOPT_HTTPGET, CURLOPT_CUSTOMREQUEST
                ), true);
            $options[CURLOPT_NOBODY] = 1;
            $options[CURLOPT_URL] = $url;
            break;
        case 'delete':
            $options = $this->_processCurlOptions($options,
                array(
                    CURLOPT_POST, CURLOPT_HTTPGET, CURLOPT_NOBODY
                ), true);
            # Customize header to HTTP DELETE
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            $options[CURLOPT_URL] = $url;
            break;
        default:
            return false;
        }
        curl_setopt_array($ch, $options);
        if ($key !== null) {
            $this->_multitasks[$key] = array(
                &$ch, $options
            );
        } else {
            $this->_multitasks[] = array(
                &$ch, $options
            );
        }
        curl_multi_add_handle($this->_multitasks_handler, $ch);
        return true;
    }

    /**
     * 以原生curl_setopt_array方式添加任务
     *
     * @param array			$options			array options
     * @param string		$key				key参数
     * @return boolean
     */
    public function AddRawTask($options, $key = null)
    {
        $ch = curl_init();
        $this->initCurl($ch);
        curl_setopt_array($ch, $options);
        if ($key !== null) {
            $this->_multitasks[$key] = array(
                &$ch, $options
            );
        } else {
            $this->_multitasks[] = array(
                &$ch, $options
            );
        }
        curl_multi_add_handle($this->_multitasks_handler, $ch);
        return true;
    }
    /**
     * 执行多任务处理
     *
     * @return array
     */
    public function exec()
    {
        if (empty($this->_multitasks)) {
            return false;
        }
        # code below are from php manual
        $active = null;
        //execute the handles
        do {
            $mrc = curl_multi_exec($this->_multitasks_handler, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($this->_multitasks_handler) != - 1) {
                do {
                    $mrc = curl_multi_exec($this->_multitasks_handler, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
        # code above are from php manual
        $ret = array();
        foreach ($this->_multitasks as $_k => $ch) {
            $ret[$_k] = $this->_multitasks[$_k][1];
            $ret[$_k]['data'] = curl_multi_getcontent($ch[0]);
            
            $info = curl_getinfo($this->_multitasks[$_k][0]);
            
            $ret[$_k]['code'] = $info['http_code'];
            $ret[$_k]['eurl'] = $info['url'];
            $ret[$_k]['length'] = $info['download_content_length'] == - 1 ? $info['size_download'] : $info['download_content_length'];
            curl_multi_remove_handle($this->_multitasks_handler, $this->_multitasks[$_k][0]);
        }
        curl_multi_close($this->_multitasks_handler);
        return $ret;
    }
    
    /**
     * 获取多任务信息
     *
     * @param int				$key
     * @param int				$opt
     */
    public function multiple_threads_request($nodes){
    	$mh = curl_multi_init();
    	$curl_array = array();
    	foreach($nodes as $i => $node) {
    		$curl_array[$i] = curl_init($node['url']);
    		curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($curl_array[$i], CURLOPT_POST, true);
    		curl_setopt($curl_array[$i], CURLOPT_URL, $node['url']);
    		curl_setopt($curl_array[$i], CURLOPT_POSTFIELDS, $node['data']);
    		curl_setopt($curl_array[$i], CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    		curl_setopt($curl_array[$i], CURLOPT_USERPWD, 'yidayida:pwpw');
    		curl_setopt($curl_array[$i], CURLOPT_TIMEOUT, 5);
    		curl_multi_add_handle($mh, $curl_array[$i]);
    	}
    	$running = NULL;
    	do {
    		usleep(10000);
    		curl_multi_exec($mh,$running);
    	} while($running > 0);
    
    	$res = array();
    	foreach($nodes as $i => $node) {
    		$res[$i] = curl_multi_getcontent($curl_array[$i]);
    	}
    
    	foreach($nodes as $i => $node) {
    		curl_multi_remove_handle($mh, $curl_array[$i]);
    	}
    	curl_multi_close($mh);
    	
    	return $res;
    }

    /**
     * 获取多任务信息
     *
     * @param int				$key
     * @param int				$opt
     */
    public function getInfo($key, $opt = null)
    {
        if (! isset($this->_multitasks[$key])) {
            return false;
        } else {
            if ($opt === null) {
                return curl_getinfo($this->_multitasks[$key][0]);
            } else {
                return curl_getinfo($this->_multitasks[$key][0], $opt);
            }
        }
    }

    /**
     * 释放资源
     *
     * @return true
     */
    public function free()
    {
        $this->_multitasks_handler = null;
        $this->_multitasks = array();
        return true;
    }
}
# EOF