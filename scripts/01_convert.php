<?php

$basePath = dirname(__DIR__);

include $basePath . '/libs/geoPHP/geoPHP.inc';

//keys = 國有, 省市有, 縣市有, 鄉鎮市有
$pool = array();
foreach (glob($basePath . '/raw/*/*/*.xml') AS $xmlFile) {
    $p = pathinfo($xmlFile);
    $pParts = explode('/', $p['dirname']);
    $cityCode = $pParts[count($pParts) - 2];
    $c = mb_convert_encoding(file_get_contents($xmlFile), 'utf-8', 'big5');
    $c = str_replace('<?xml version="1.0" encoding="BIG5"?>', '<?xml version="1.0" encoding="UTF-8"?>', $c);
    $objs = (array) new SimpleXMLElement($c);
    foreach ($objs AS $obj) {
        $obj = (array) $obj;
        if (count(array_keys($obj)) !== 0) {
            foreach ($obj['土地標示部'] AS $land) {
                $key = $cityCode . (string) $land->段代碼 . (string) $land->地號;
                $land = json_decode(json_encode($land), true);
                foreach ($land AS $k => $v) {
                    if (empty($v)) {
                        $land[$k] = '';
                    }
                }
                $pool[$key] = $land;
            }
        }
    }
}

$fh = fopen($basePath . '/tmp/missing.csv', 'w');
fputcsv($fh, array('city', 'section', 'land no'));
foreach (glob($basePath . '/raw/*/*/*.kml') AS $xmlFile) {
    $p = pathinfo($xmlFile);
    $pParts = explode('/', $p['dirname']);
    $cityCode = $pParts[count($pParts) - 2];
    $c = new SimpleXMLElement(file_get_contents($xmlFile));
    $sectionCode = $cityCode . substr((string) $c->Document->name, 0, 4);
    $path = $basePath . '/tmp/json/' . $sectionCode;
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
    foreach ($c->Document->Placemark AS $p) {
        $n = (string) $p->name;
        $n = str_replace('地號', '', $n);
        $parts = explode('-', $n);
        if (count($parts) !== 1) {
            $key = $sectionCode . str_pad($parts[0], 4, '0', STR_PAD_LEFT) . str_pad($parts[1], 4, '0', STR_PAD_LEFT);
        } else {
            $key = $sectionCode . str_pad($parts[0], 4, '0', STR_PAD_LEFT) . '0000';
        }
        $geo = geoPHP::load($p->asXML(), 'kml');
        $targetFile = $path . '/' . $key . '.json';
        if (!file_exists($targetFile)) {
            $f = new stdClass();
            $f->type = 'Feature';
            if (!isset($pool[$key])) {
              fputcsv($fh, array($cityCode, (string)$c->Document->name, (string)$p->name));
                $f->properties = new stdClass();
            } else {
                $f->properties = $pool[$key];
            }

            $f->geometry = json_decode($geo->out('json'));
            file_put_contents($targetFile, json_encode($f));
        } else {
            $json = json_decode(file_get_contents($targetFile));
            if ($json->geometry->type !== 'MultiPolygon') {
                $json->geometry->type = 'MultiPolygon';
                $json->geometry->coordinates = array($json->geometry->coordinates);
            }
            $f = json_decode($geo->out('json'));
            $json->geometry->coordinates[] = $f->coordinates;
            file_put_contents($targetFile, json_encode($json));
        }
        error_log($key);
    }
}

// foreach(glob($basePath . '/*_*.zip') AS $zipFile) {
//   exec("unzip {$zipFile}");
// }
