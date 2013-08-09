<?php

namespace Scalia\SphinxSearch;

use Illuminate\Support\Facades\Facade;

class SphinxSearchFacade extends Facade {
	protected static function getFacadeAccessor()
	{
		return 'sphinxsearch';
	}
}
