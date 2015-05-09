<?php
set_time_limit(0);
ini_set('memory_limit','512M');
//header('Content-Encoding: none;');
header('X-Accel-Buffering: no');

// Does config exist?
$exists = file_exists($dir . "/../config.php");
if($exists)
	die("Sorry, you cannot install a board that already has a config setup");

ob_implicit_flush(true);
ob_end_flush();

output('<!doctype html><html><head><title>zKillboard Installer</title><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js" type="text/javascript"></script>
<script src="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js" type="text/javascript"></script>
<script type="text/javascript">$(function () { $("[rel=\'tooltip\']").tooltip({placement:"left"});});</script>
<script type="text/javascript">$("#container").animate({ scrollTop: $("#container")[0].scrollHeight}, 1000);</script>
<style>body{margin:40px;}.stepwizard-step p {margin-top: 10px;}.stepwizard-row {display: table-row;}.stepwizard {display: table;width: 100%;position: relative;}
.stepwizard-step button[disabled] {opacity: 1 !important;filter: alpha(opacity=100) !important;}.stepwizard-row:before {top: 14px;bottom: 0;position: absolute;
content: " ";width: 100%;height: 1px;background-color: #ccc;z-order: 0;}.stepwizard-step {display: table-cell;text-align: center;position: relative;}
.btn-circle {width: 30px;height: 30px;text-align: center;padding: 6px 0;font-size: 12px;line-height: 1.428571429;border-radius: 15px;}</style></head>
<body><div class="container"><div class="stepwizard"><div class="stepwizard-row"><div class="stepwizard-step">
<button type="button" class="btn btn-default btn-circle">1</button><p>Initialization</p></div><div class="stepwizard-step">
<button type="button" class="btn btn-default btn-circle">2</button><p>Information</p></div><div class="stepwizard-step">
<button type="button" class="btn btn-primary btn-circle">3</button><p>Installation</p></div><div class="stepwizard-step">
<button type="button" class="btn btn-default btn-circle">4</button><p>Finalization</p></div></div></div><div class="row setup-content" id="step-3">');


if($_POST)
{
	$settings = array();
	$settings["dbuser"] = post("databaseusername");
	$settings["dbpassword"] = post("databasepassword");
	$settings["dbname"] = post("databasename");
	$settings["dbhost"] = post("databasehost");
	$settings["memcache"] = post("memcachehost");
	$settings["memcacheport"] = post("memcacheport");
	$settings["redis"] = post("redishost");
	$settings["redisport"] = post("redisport");
	$settings["phealcachelocation"] = post("phealcache");
	$settings["baseaddr"] = post("domainname");
	$settings["logfile"] = post("logfile");
	$settings["imageserver"] = post("imageserver");
	$settings["apiserver"] = post("apiserver");
	$settings["cookiesecret"] = post("cookiesecret");
	$adminPassword = post("adminpassword");

	output('<div class="col-xs-12"><div class="col-md-12"><h2>If any part errors, hover over the icon to get full information.</h2><table class="table table-striped"><thead><tr><td></td><td class="col-lg-1"></td></tr></thead><tbody><tr><td>Testing the database connection.</td>');

	$dbSuccess = true;
	$reason = "";
	// Test out the db params first..
	$dbname = $settings["dbname"];
	$dbhost = $settings["dbhost"];
	$dsn = "mysql:dbname=" . $settings["dbname"] . ";host=" . $settings["dbhost"];
	try
	{
		$pdo = new PDO($dsn, $settings["dbuser"], $settings["dbpassword"], array(
			PDO::ATTR_TIMEOUT => 10,
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_PERSISTENT => false,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
			)
		);
	}
	catch(Exception $e)
	{
		$dbSuccess = false;
		$reason = $e->getMessage();
	}

	if($dbSuccess == false)
	{
		output('<td><button type="button" class="btn btn-danger btn-circle" rel="tooltip" title="'.$reason.'"><i class="glyphicon glyphicon-warning-sign"></i></button></td></tr>');
		die();
	}
	else
		output('<td><button type="button" class="btn btn-primary btn-circle"><i class="glyphicon glyphicon-ok"></i></button></td></tr>');

	$configCreate = true;
	output('</tr><tr><td>Creating the config file</td>');

	// Get default config
	$configFile = file_get_contents(__DIR__."/../config.new.php");

	// Replace in the config
	foreach($settings as $key => $value)
		$configFile = str_replace("%$key%", $value, $configFile);

	// Save the config, and try and load it
	$configLocation = __DIR__."/../../config.php";
	if(file_put_contents($configLocation, $configFile) === false)
	{
		$configCreate = false;
		$reason = "Error placing the config file. Most likely a write issue.";
	}

	if($configCreate == false)
	{
		output('<td><button type="button" class="btn btn-danger btn-circle" rel="tooltip" title="'.$reason.'"><i class="glyphicon glyphicon-warning-sign"></i></button></td></tr>');
		die();
	}
	else
		output('<td><button type="button" class="btn btn-primary btn-circle"><i class="glyphicon glyphicon-ok"></i></button></td></tr>');

	output('</tr><tr><td>Downloading composer</td>');

	// Lets install composer
	chdir(__DIR__."/../../");
	exec("php -r \"eval('?>'.file_get_contents('https://getcomposer.org/installer'));\"");
	output('<td><button type="button" class="btn btn-primary btn-circle"><i class="glyphicon glyphicon-ok"></i></button></td></tr>');

	output('</tr><tr><td>Installing vendor files</td>');
	chdir(__DIR__."/../../");
	exec("php composer.phar install --optimize-autoloader");
	output('<td><button type="button" class="btn btn-primary btn-circle"><i class="glyphicon glyphicon-ok"></i></button></td></tr>');

	// Vendor is installed, config works, lets load the init!
	require_once("$dir/../init.php");

	// Time to import the database !
	output('</tr><tr><td>Installing the database tables</td><td></td></tr>');
	$sqlFiles = scandir(__DIR__."/../../install/sql/"); // Load the SQL files from the old installer dir for now
	foreach($sqlFiles as $file)
	{
		if($file == "." || $file == "..")
			continue;

		if(stristr($file, ".sql"))
		{
			$table = str_replace(".sql", "", $file);
			$table = str_replace(".gz", "", $table);
			$sqlFile = __DIR__."/../../install/sql/$file";
			output('<tr><td>Adding table: <b>'.$table.'</b></td>');
			output('<td>'. loadFile($sqlFile) .'</td></tr>');
		}
	}

	// Update the CCP Tables
	updateCCPData($settings["dbuser"], $settings["dbpassword"], $settings["dbname"], $settings["dbhost"]);

	// Add the admin user
	output('<tr><td>Adding admin user</b></td><td></td></tr>');
	Db::execute("INSERT INTO zz_users (username, moderator, admin, password) VALUES ('admin', 1, 1, '".$adminPassword."')");

	// Create the cache directories
	output('<tr><td>Creating cache directories</td><td></td></tr>');
	@mkdir("$dir/../cache/");
	@mkdir("$dir/../cache/sessions/");
	@mkdir("$dir/../cache/pheal/");

	output('<tr><td>Done, click the button below to move onto the final page.</td><td></td></tr>');
	output('<tr><td style="text-align: center !important;">
				<p><a class="btn btn-lg btn-success" href="?step=4">Step 4</a></p>
			</td></tr>');
	// Keep at the bottom of the tables..
	output('</tr></tbody></table>');
}

output('</div></div></body></html>');

// it has to die, otherwise it'll try and load a template with twig /o\
die();
//Grab the post info!
function post($var)
{
	return isset($_POST[$var]) ? $_POST[$var] : null;
}

// Part function
function part($part = null)
{
	return isset($_GET["part"]) ? (int) $_GET["part"] : 0;
}

// Output it mothafuckaa
function output($html)
{
	echo $html;
	flush();
}

function loadFile($file)
{
	if (stristr($file, ".gz"))
		$handle = gzopen($file, "r");
	else
		$handle = fopen($file, "r");

	$query = "";
	while ($buffer = fgets($handle))
	{
		$query .= $buffer;
		if (strpos($query, ";") !== false)
		{
			$query = str_replace(";", "", $query);
			Db::execute($query);
			$query = "";
		}
	}
	fclose($handle);
}

function updateCCPData($dbUser, $dbPassword, $dbName, $dbHost)
{
	$url = "https://www.fuzzwork.co.uk/dump/";
	$cacheDir = __DIR__ . "/../../cache/update";

	// If the cache dir doesn't exist, create it
	if(!file_exists($cacheDir))
		mkdir($cacheDir);

	$dbFiles = array("dgmAttributeCategories", "dgmAttributeTypes", "dgmEffects", "dgmTypeAttributes", "dgmTypeEffects", "invFlags", "invGroups", "invTypes", "mapDenormalize", "mapRegions", "mapSolarSystems");
	$type = ".sql.bz2";

	// Now run through each db table, and insert them !
	foreach($dbFiles as $file)
	{
		output("</tr><tr><td><b>Updating data in table:</b> {$file}</td>");
		$dataURL = $url . "latest/" . $file . $type;

		// Get and extract, it's simpler to use execs for this, than to actually do it with php
		exec("wget -q $dataURL -O $cacheDir/$file$type");
		exec("bzip2 -q -d $cacheDir/$file$type");

		// Now get the sql so we can alter a few things
		$data = file_get_contents($cacheDir . "/" . $file . ".sql");

		// Systems and regions need to be renamed
		if($file == "mapRegions")
			$name = "regions";
		if($file == "mapSolarSystems")
			$name = "systems";

		if(isset($name))
			$data = str_replace($file, "ccp_$name", $data);
		else
			$data = str_replace($file, "ccp_$file", $data);

		$dataParts = explode(";\n", $data);

		foreach($dataParts as $q)
		{
			$query = $q . ";";
			Db::execute($query);
		}

		// Delete the .sql file
		unlink("$cacheDir/$file.sql");

		// Output html
		output('<td><button type="button" class="btn btn-primary btn-circle"><i class="glyphicon glyphicon-ok"></i></button></td></tr>');
	}
}
