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

class OAuth
{
	public static function eveSSOLoginURL()
	{
		global $ssoServer, $ssoResponseType, $ssoRedirectURI, $ssoClientID, $ssoScope, $ssoState;
		return "{$ssoServer}/oauth/authorize?response_type={$ssoResponseType}&redirect_uri={$ssoRedirectURI}&client_id={$ssoClientID}&scope={$ssoScope}&state={$ssoState}";
	}

	public static function eveSSOLoginToken($code, $state)
	{
		global $ssoServer, $ssoSecret, $ssoClientID;

		$tokenURL = $ssoServer . "/oauth/token";
		$b64 = $ssoClientID . ":" . $ssoSecret;
		$base64 = base64_encode($b64);

		$header = array();
		$header[] = "Authorization: Basic {$base64}";

		$fields = array(
			"grant_type" => "authorization_code",
			"code" => $code
		);

		$data = Util::postData($tokenURL, $fields, $header);

		$data = json_decode($data);
		$accessToken = $data->access_token;

		self::eveSSOLoginVerify($accessToken);

	}

	public static function eveSSOLoginVerify($accessToken)
	{
		global $ssoServer;

		$verifyURL = $ssoServer . "/oauth/verify";

		$header = array();
		$header[] = "Authorization: Bearer {$accessToken}";

		$data = Util::postData($verifyURL, NULL, $header);

		self::eveSSOLogin($data);
	}

	public static function eveSSOLogin($data = NULL)
	{
		global $cookie_name, $cookie_time, $cookie_ssl, $baseAddr, $app;

		$data = json_decode($data);
		$characterID = (int) $data->CharacterID;
		$affiliationInfo = Info::getCharacterAffiliations($characterID);

		$exists = Db::queryField("SELECT merged FROM zz_users WHERE characterID = :characterID", "merged", array(":characterID" => $characterID), 0);
		if(!$exists || $exists == 0) // Exists should never be 0 actually, it should always be null or 1.. but lets catch it if it is for some strange reason..
		{
			// Insert the data to zz_users_crest
			Db::execute("INSERT IGNORE INTO zz_users_crest (characterID, characterName, scopes, tokenType, characterOwnerHash, corporationID, corporationName, corporationTicker, allianceID, allianceName, allianceTicker) VALUES (:characterID, :characterName, :scopes, :tokenType, :characterOwnerHash, :corporationID, :corporationName, :corporationTicker, :allianceID, :allianceName, :allianceTicker)", array(":characterID" => $data->CharacterID, ":characterName" => $data->CharacterName, ":scopes" => $data->Scopes, ":tokenType" => $data->TokenType, ":characterOwnerHash" => $data->CharacterOwnerHash, ":corporationID" => $affiliationInfo["corporationID"], ":corporationName" => $affiliationInfo["corporationName"], ":corporationTicker" => $affiliationInfo["corporationTicker"], ":allianceID"  => $affiliationInfo["allianceID"], ":allianceName" => $affiliationInfo["allianceName"], ":allianceTicker" => $affiliationInfo["allianceTicker"]));

			// Send the user to the merge page
			header("Location: /merge/{$characterID}/");
		}
		else
		{
			// User exists, and is already registered, merged etc. etc.. Just login
			$password = Db::queryField("SELECT password FROM zz_users WHERE characterID = :characterID", "password", array(":characterID" => $characterID));
			$username = Db::queryField("SELECT username FROM zz_users WHERE characterID = :characterID", "username", array(":characterID" => $characterID));
			$userID = Db::queryField("SELECT id FROM zz_users WHERE characterID = :characterID", "id", array(":characterID" => $characterID));
			$passwordHash = Password::genPassword($password);
			$hash = $username . "/" . hash("sha256", $username . $passwordHash . time());
			$app->setEncryptedCookie($cookie_name, $hash, time() + $cookie_time, "/", $baseAddr, $cookie_ssl, true);
			$validTill = date("Y-m-d H:i:s", time() + $cookie_time);
			$userAgent = $_SERVER["HTTP_USER_AGENT"];
			$ip = IP::get();
			Db::execute("INSERT INTO zz_users_sessions (userID, sessionHash, validTill, userAgent, ip) VALUES (:userID, :sessionHash, :validTill, :userAgent, :ip)", 
				array(":userID" => $userID, ":sessionHash" => $hash, ":validTill" => $validTill, ":userAgent" => $userAgent, ":ip" => $ip));
			$_SESSION["loggedin"] = $data->CharacterName;
			header("Location: /");
		}
	}
}