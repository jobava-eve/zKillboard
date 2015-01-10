<?php
/* zKillboard
 * Copyright (C) 2012-2015 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

global $cookie_name;
$requesturi = "";
if(isset($_SERVER["HTTP_REFERER"])) $requesturi = $_SERVER["HTTP_REFERER"];
$sessionCookie = $app->getEncryptedCookie($cookie_name, false);
// remove the entry from the database
Db::execute("DELETE FROM zz_users_sessions WHERE sessionHash = :hash", array(":hash" => $sessionCookie));
unset($_SESSION["loggedin"]);
$app->view(new \Slim\Views\Twig());
$twig = $app->view()->getEnvironment();
$twig->addGlobal("sessionusername", "");
$twig->addGlobal("sessionuserid", "");
$twig->addGlobal("sessionadmin", "");
$twig->addGlobal("sessionmoderator", "");
setcookie($cookie_name, "", time()-$cookie_time, "/", $baseAddr);
setcookie($cookie_name, "", time()-$cookie_time, "/", ".".$baseAddr);
if (isset($requesturi) && $requesturi != "") $app->redirect($requesturi);
else $app->render("logout.html", array("message" => "You are now logged out"));
