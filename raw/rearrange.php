<?php
foreach(glob(__DIR__ . '/*.*ml') AS $file) {
    $p = pathinfo($file);
    $prefix = strtoupper(substr($p['filename'], 0, 1));
	$targetPath = __DIR__ . '/' . $prefix;
	if(!file_exists($targetPath)) {
		mkdir($targetPath, 0777, true);
	}
	$targetFile = $targetPath . '/' . $p['basename'];
	exec("git mv {$file} {$targetFile}");
}
