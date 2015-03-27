<?php
error_reporting(1);
error_reporting(E_ALL);

// Base dir
$dir = __DIR__;

// Defaults
$html = null;
$array = array();

// Load autoloader
require_once("$dir/vendor/autoload.php");

// Load twig
$loader = new Twig_Loader_Filesystem("$dir/templates/");
$twig = new Twig_Environment($loader);

// Step handler..
$step = step("step");

// Load step
require_once("$dir/views/step$step.php");

// Does config exist?
$exists = file_exists($dir . "/../config.php");
$array["configExists"] = $exists;

if($exists && in_array($_GET["step"], array("1", "2", "3")))
	die("Sorry, you cannot install a board that already has a config setup");

// Render it
echo $twig->render($html, $array);

// Step function
function step($step = null)
{
	return isset($_GET["step"]) ? (int) $_GET["step"] : 0;
}