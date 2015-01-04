<?php

class Killmail
{
	public static function get($killID)
	{
		$kill = Cache::get("Kill$killID");
		if ($kill != null) return $kill;

		$kill = Db::queryField("select kill_json from zz_killmails where killID = :killID", "kill_json", array(":killID" => $killID));
		if ($kill != '') {
			Cache::set("Kill$killID", $kill);
			return $kill;
		}
		
		$file = static::getFile($killID);
		if (!file_exists($file)) return null;
		
		$contents = file_get_contents($file);
		$kills = json_decode($contents, true);
		if (!isset($kills["$killID"])) return null;
		$kill = $kills["$killID"];
		if ($kill != '') Cache::set("Kill$killID", $kill);
		return $kill;
	}

	public static function massSet($kills)
	{
		// Verify kills are part of the same set
		$files = array();
		$killIDs = array();
		foreach($kills as $kill)
		{
			$killID = $kill["killID"];
			$killIDs[] = $killID;
			$file = static::getFile($killID, true);
			if (!in_array($file, $files)) $files[] = $file;
		}
		if (count($files) > 1) throw new Exception("Invalid set.");
		$file = $files[0];

				$sem = sem_get(1234);
				if (!sem_acquire($sem)) throw new Exception("Unable to obtain semaphore");
				if (!file_exists($file)) $json = array();
				else
				{
						$contents = file_get_contents($file);
						$json = json_decode($contents, true);
				}

		foreach($kills as $kill)
		{
			$killID = $kill["killID"];
			$killJson = $kill["json"];
			if ($killJson == "" || $killJson == "{}") continue;
					$json["$killID"] = $killJson;
		}

				$contents = json_encode($json);
				file_put_contents($file, $contents, LOCK_EX);
		Db::execute("update zz_killmails set kill_json = '' where killID in (" . implode(",", $killIDs) . ")");
				sem_release($sem);
	}

	protected static function getFile($killID, $createDir = false)
	{
		global $baseDir;
		$kmBase = "$baseDir/killmails";

		$id = $killID;
		$botDir = abs($id % 1000);
		while (strlen("$botDir") < 3) $botDir = "0" . $botDir;
		$id = (int) $id / 1000;
		$midDir = abs($id % 1000);
		while (strlen("$midDir") < 3) $midDir = "0" . $midDir;
		$id = (int) $id / 1000;
		$topDir = $id % 1000;

		while (strlen("$topDir") < 4) $topDir = "0" . $topDir;
		$dir = "$kmBase/d$topDir/";
		if ($createDir) @mkdir($dir, 0700, true);

		$file = "$dir/k$midDir.json";
		return $file;
	}

	// https://forums.eveonline.com/default.aspx?g=posts&m=4900335#post4900335
	public static function getCrestHash($killID)
	{
		$killmail = json_decode(Killmail::get($killID), true);

		$victim = $killmail["victim"];
		$victimID = $victim["characterID"] == 0 ? "None" : $victim["characterID"];

		$attackers = $killmail["attackers"];
		$attacker = null;
		foreach($attackers as $att)
		{
			if ($att["finalBlow"] != 0) $attacker = $att;
		}
		if ($attacker == null) $attacker = $attackers[0];
		$attackerID = $attacker["characterID"] == 0 ? "None" : $attacker["characterID"];

		$shipTypeID = $victim["shipTypeID"];

		$dttm = (strtotime($killmail["killTime"]) * 10000000) + 116444736000000000;

		$string = "$victimID$attackerID$shipTypeID$dttm";

		$sha = sha1($string);
		return $sha;
	}
}
