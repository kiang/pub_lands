<?php

$basePath = dirname(__DIR__);
$targetPath = $basePath . '/json/city';
if(!file_exists($targetPath)) {
  mkdir($targetPath, 0777, true);
}

$jsonCity = json_decode(file_get_contents($basePath . '/json/city.topo.json'), true);
$code = array(
  '嘉義市嘉義市' => '10020',
  '苗栗縣頭份巿' => '10005050',
  '新竹市新竹市' => '10018',
  '屏東縣屏東巿' => '10013010',
  '屏東縣霧台鄉' => '10013270',
  '屏東縣?埔鄉' => '10013100',
  '臺東縣金?鄉' => '10014140',
  '金沙鎮' => '09020020',
);
foreach($jsonCity['objects']['city']['geometries'] AS $city) {
  $code[$city['properties']['COUNTYNAME'] . $city['properties']['TOWNNAME']] = $city['properties']['TOWNCODE'];
}
$dic = array(
  '台東' => '臺東',
);
$city = array();
foreach(glob($basePath . '/tmp/json/*/*.json') AS $jsonFile) {
  $json = json_decode(file_get_contents($jsonFile), true);
  if(isset($json['properties']['縣市'])) {
    $cityName = strtr($json['properties']['縣市'] . $json['properties']['鄉鎮市區'], $dic);
    $cityCode = $code[$cityName];
  } else {
    $cityCode = '---';
  }
  if(!isset($city[$cityCode])) {
    $city[$cityCode] = new stdClass();
    $city[$cityCode]->type = 'FeatureCollection';
    $city[$cityCode]->features = array();
  }
  $city[$cityCode]->features[] = json_decode(file_get_contents($jsonFile));
}

$tmpFile = $basePath . '/tmp/tmp.json';
foreach($city AS $cityCode => $fc) {
  if(file_exists($tmpFile)) {
    unlink($tmpFile);
  }
  file_put_contents($tmpFile, json_encode($fc));
  exec("/usr/local/bin/mapshaper -i {$tmpFile} -o format=topojson {$targetPath}/{$cityCode}.json");
}
