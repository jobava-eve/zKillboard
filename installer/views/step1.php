<?php

$fullphpversion = PHP_VERSION;
$phpversion = PHP_VERSION_ID;
$hhvm = false;
$phpabovefivefour = false;
$memcache = false;
$memcached = false;
$apc = false;
$redis = false;
$mysql = false;
$pdo = false;

if(stristr($fullphpversion, "hhvm"))
	$hhvm = true;

if($phpversion > 50400)
	$phpabovefivefour = true;

$extensions = get_loaded_extensions();
foreach($extensions as $ext)
{
	if($ext == "memcache")
		$memcache = true;
	if($ext == "memcached")
		$memcached = true;
	if($ext == "apc")
		$apc = true;
	if($ext == "redis")
		$redis = true;
	if($ext == "mysql")
		$mysql = true;
	if($ext == "pdo_mysql" || $ext == "PDO")
		$pdo = true;
}

$cachewriteable = is_writeable("$dir/../cache/");

$html = "step1.html";
$array = array(
	"phpversion" => $phpabovefivefour,
	"hhvm" => $hhvm,
	"memcache" => $memcache,
	"memcached" => $memcached,
	"apc" => $apc,
	"redis" => $redis,
	"cachewriteable" => $cachewriteable,
	"mysql" => $mysql,
	"pdo" => $pdo
);