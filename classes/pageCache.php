<?php
class pageCache extends \Slim\Middleware
{
	public function call()
	{
		global $theme;
		if(User::isLoggedIn())
		{
			$this->next->call();
			return;
		}

		$pageURL = $this->app->request()->getResourceUri();
		$md5 = md5($pageURL . $theme);
		$fileCache = new FileCache();

		// Pages to cache
		$pages = array(
			"/kill/" => 60*60*24, // Kill detail pages can easily be cached for many hours since they rarely change..
			"/system/" => 60*60, // 1 hour cache on the system page
			"/region/" => 60*60, // 1 hour cache on the region page
			"/group/" => 60*60, // 1 hour cache on the groups view
			"/ship/" => 60*15, // 15 minutes on ship view
			"/character/" => 60*5, // 5 minutes on character view
			"/corporation/" => 60*5, // 5 minutes on corporation view
			"/alliance/" => 60*5, // 5 minutes on alliance view
			"/faction/" => 60*15, // 15 minutes on faction view
			"/item/" => 60*60*24, // 1 Day on item view, shit barely changes..
			"/information/" => 60*5, // 5 minutes on information, tho it's probably not needed tbh
		);

		// Default cachetime is 0seconds
		$cacheTime = 0;
		foreach($pages as $page => $cTime)
		{
			if(stristr($pageURL, $page)) // Only do shit on the kill page for now... Shou
			{
				$cacheTime = $cTime;
			}
		}

		// Default headers
		header("X-PageCache: false");
		header("X-PageCache-Time: {$cacheTime}");

		// Application response
		$rsp = $this->app->response();

		// Get the cached result if it exists
		$data = $fileCache->get($md5);
		if (isset($data) && !empty($data["body"]))
		{
			// cache hit... return the cached content
			header("X-PageCache: true");
			$rsp["Content-Type"] = $data["content_type"];
			$rsp->body($data["body"]);
			return;
		}

		// cache miss... continue on to generate the page
		$this->next->call();

		// cache result for future look up
		if ($rsp->status() == 200) {
			if($cacheTime > 0)
				$fileCache->set($md5, array("key" => $pageURL, "content_type" => $rsp["Content-Type"], "body" => $rsp->body()), $cacheTime);
		}
	}
}
