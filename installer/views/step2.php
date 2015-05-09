<?php
$cookieSecret = uniqid(time());
$adminPassword = substr(str_shuffle('abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 12);
$serverName = $_SERVER["SERVER_NAME"];


$html = "step2.html";
$array = array(
	"cookieSecret" => $cookieSecret,
	"adminPassword" => $adminPassword,
	"serverName" => $serverName
);