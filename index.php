<?php
// Include Init
require_once( "init.php" );

// initiate the timer!
$timer = new Timer();

// Starting Slim Framework
$app = new \Slim\Slim($config);
$app->add(new pageCache());

// Session
$session = new zKBSession();
session_set_save_handler($session, true);
session_cache_limiter(false);
session_start();

// Check if the user has autologin turned on
if(!User::isLoggedIn()) User::autoLogin();

// Detect mobile devices
$detect = new Mobile_Detect();
$isMobile = $detect->isMobile() ? true : false;
$isTablet = $detect->isTablet() ? true : false;

// Theme
if(User::isLoggedIn())
	$theme = UserConfig::get("theme");
if(!isset($theme))
	$theme = "zkillboard";
elseif(!is_dir("themes/$theme"))
	$theme = "zkillboard";
if($isMobile && !$isTablet)
	$theme = "mobile";

$app->config(array("templates.path" => $baseDir."themes/" . $theme));

// Error handling
$app->error(function (\Exception $e) use ($app){
    include ( "view/error.php" );
});

// Load the routes - always keep at the bottom of the require list ;)
include( "routes.php" );

// Load twig stuff
include( "twig.php" );

// Load the theme stuff AFTER routes and Twig, so themers can add crap to twig's global space
require_once("themes/$theme/$theme.php");

// Tell statsD that there is a hit
StatsD::increment("website_hit");
StatsD::timing("website_loadTime", Util::pageTimer());

// Run the thing!
$app->run();
