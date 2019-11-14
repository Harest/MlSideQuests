<?php
/* Cache Manager Service (Memcached) for SQL requests result, etc.
*/
class cacheManager
{
	private $memcached = null;
	private $cacheHit = false;
	
	public function __construct()
	{
		$this->memcached = (class_exists('Memcached')) ? new \Memcached() : null;
		if ($this->memcached != null) $this->memcached->addServer('localhost', 11211);
	}
	
	// Check that Memcached is ready to be used
	public function ready()
	{
		return ($this->memcached != null);
	}
	
	// Get a var from the cache
	public function get($cacheId)
	{
		if ($this->ready())
		{
			$this->setCacheHit(false);
			$cacheContent = $this->memcached->get($cacheId);
			if ($cacheContent != false) $this->setCacheHit(true);
			return $cacheContent;
		}
		return false;
	}
	
	// Set a var in the cache
	public function set($cacheId, $cacheContent, $cacheTTL)
	{
		if ($this->ready())
		{
			return $this->memcached->set($cacheId, $cacheContent, time() + $cacheTTL);
		}
		return false;
	}
	
	public function getCacheHit()
	{
		return $this->cacheHit;
	}
	
	public function setCacheHit($cacheHit)
	{
		$this->cacheHit = $cacheHit;
	}
}