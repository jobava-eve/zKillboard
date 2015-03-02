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

class zKBSession implements SessionHandlerInterface
{
	private $ttl = 7200; // 2hrs of cache

	public function open($savePath, $sessionName)
	{
		return true;
	}

	public function close()
	{
		return true;
	}

	public function read($id)
	{
		$data = Cache::get($id);
		if(is_array($data)) // Just to make sure that we're returning a string and not an array, eventho that shouldn't technically be possible..
			return "";
		return Cache::get($id);
	}

	public function write($id, $data)
	{
		Cache::set($id, $data, $this->ttl);
		return true;
	}

	public function destroy($id)
	{
		Cache::delete($id);
		return true;
	}

	public function gc($maxlifetime)
	{
		return true;
	}
}