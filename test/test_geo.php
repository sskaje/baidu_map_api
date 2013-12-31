<?php

require(__DIR__ . '/../src/BaiduMap.init.php');

define('BAIDU_LBSCLOUD_APPKEY', '61fa817d010f47bdaa50b98cec209895');

$client = new spBaiduLBSCloudClient;
/*
$databox = $client->createDataBox('aaa', spBaiduLBSCloudClient::GEO_TYPE_POINT);
$detail = $databox->detail();
var_dump($detail);
*/
$res = $client->searchDataBox();
foreach ($res as $o) {
    var_dump($o->id, $o->name);
    $o->update('new name' . mt_rand() % 100);
}

if (is_callable($o, 'delete')) $o->delete();

