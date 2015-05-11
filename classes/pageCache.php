<?php
class pageCache extends \Slim\Middleware
{
	public function call()
	{
		$pageURL = $this->app->request()->getResourceUri();
		$fileCache = new FileCache();

		// Pages to cache
		$pages = array(
			"/kill/" => "3600",
		);

		// Default cachetime is 15seconds
		$cacheTime = 15;
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
		$data = $fileCache->get(md5($pageURL));
		if ($data) {
			// cache hit... return the cached content
			header("XPageCache: true");
			$rsp["Content-Type"] = $data["content_type"];
			$rsp->body($data["body"]);
			return;
		}

		// cache miss... continue on to generate the page
		$this->next->call();

		// cache result for future look up
		if ($rsp->status() == 200) {
			if($cacheTime > 0)
				$fileCache->set(md5($pageURL), array("key" => $pageURL, "content_type" => $rsp["Content-Type"], "body" => $rsp->body()), $cacheTime);
		}
	}
}