<?php
/* StatsD
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

class StatsD
{
	private static function init()
	{
		global $statsd, $statsdserver, $statsdport, $statsdnamespace, $statsdglobalnamespace;

		// If statsD isn't enabled just return and do nothing..
		if(!$statsd)
			return;

		$connection = new \Domnikl\Statsd\Connection\UdpSocket($statsdserver, $statsdport);
		$statsd = new \Domnikl\Statsd\Client($connection, $statsdnamespace);

		// Global name space
		$statsd->setNamespace($statsdglobalnamespace);

		return $statsd;
	}

	public static function increment($name, $amount = 1)
	{
		$statsd = self::init();

		if($statsd)
			$statsd->increment($name, $amount);
	}

	public static function timing($name, $time)
	{
		$statsd = self::init();

		if($statsd)
			$statsd->timing($name, $time);
	}

	public static function gauge($name, $amount)
	{
		$statsd = self::init();

		if($statsd)
			$statsd->gauge($name, $amount);
	}
}
