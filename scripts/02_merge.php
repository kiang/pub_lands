<?php

$basePath = dirname(__DIR__);

include $basePath . '/libs/geoPHP/geoPHP.inc';

$targetPath = $basePath . '/tmp/section';
if(!file_exists($targetPath)) {
  mkdir($targetPath, 0777, true);
}

foreach(glob($basePath . '/tmp/json/*') AS $section) {
  $geoPHP = new geoPHP();
  $ps = pathinfo($section);
  $targetFile = $targetPath . '/' . $ps['basename'] . '.json';
  $g = $f = false;
  foreach(glob($section . '/*.json') AS $jsonFile) {
    $j = file_get_contents($jsonFile);
    if(false === $g) {
      $g = $geoPHP::load($j, 'json');
    } else {
      try {
        $g = $g->union($geoPHP::load($j, 'json'));
      } catch (Exception $e) {
        //skip
      }
    }
    if(false === $f) {
      $jd = json_decode($j, true);
      if(isset($jd['properties']['縣市'])) {
        $f = new stdClass();
        $f->type = 'Feature';
        $f->properties = array(
          '縣市' => $jd['properties']['縣市'],
          '鄉鎮市區' => $jd['properties']['鄉鎮市區'],
          '段代碼' => $jd['properties']['段代碼'],
          '段小段' => $jd['properties']['段小段'],
        );

      }
    }
  }
  if(false === $f) {
    $f = new stdClass();
    $f->type = 'Feature';
  }
  $f->geometry = json_decode($g->out('json'));
  file_put_contents($targetFile, json_encode($f));
  error_log($targetFile);
}
