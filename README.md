[![Stories in Ready](https://badge.waffle.io/scalia/sphinxsearch.png?label=ready&title=Ready)](https://waffle.io/scalia/sphinxsearch)
# Sphinx Search
[![Latest Unstable Version](https://poser.pugx.org/scalia/sphinxsearch/v/unstable.png)](https://packagist.org/packages/scalia/sphinxsearch)[![Total Downloads](https://poser.pugx.org/scalia/sphinxsearch/downloads.png)](https://packagist.org/packages/scalia/sphinxsearch)

Sphinx Search is a package for Laravel 4 which queries Sphinxsearch and integrates with Eloquent.


## Installation

Add `scalia/sphinxsearch` to `composer.json`.

    "scalia/sphinxsearch": "dev-master"
    
Run `composer update` to pull down the latest version of Sphinx Search.

Now open up `app/config/app.php` and add the service provider to your `providers` array.

    'providers' => array(
        'Scalia\SphinxSearch\SphinxSearchServiceProvider',
    )

Now add the alias.

    'aliases' => array(
        'SphinxSearch' => 'Scalia\SphinxSearch\SphinxSearchFacade',
    )


## Configuration

To use Sphinx Search, you need to configure your indexes and what model it should query. To do so, publish the configuration into your app.

    php artisan config:publish scalia/sphinxsearch

This will create the file `app/config/packages/scalia/sphinxsearch/config.php`. Modify as needed the host and port, and configure the searches, binding them to a table and id column.

    return array (
        'host'    => '127.0.0.1',
        'port'    => 9312,
        'default_search' => 'default',

        'searches' => array (
            'default' => array(
                'indexes' => array('main','delta'),
                'mapping' => array(
                    'table'  => 'keyword',
                    'column' => 'id'
                )
            ),
        )
    );

Or disable the model querying to just get a list of result id's.

    return array (
        'host'    => '127.0.0.1',
        'port'    => 9312,
        'default_search' => 'default',

        'searches' => array (
            'default' => array(
                'indexes' => array('main','delta')
            ),
        )
    );


## Usage


Basic query

    $results = SphinxSearch::search('my query')->get();


Query another Sphinx search with limit and filters.

    $results = SphinxSearch::search('my query', 'search_name')
        ->limit(30)
        ->filter('attribute', array(1, 2))
        ->range('int_attribute', 1, 10)
        ->get();


Query with match and sort type specified.

    $result = SphinxSearch::search('my query', 'isearch_name')
        ->setFieldWeights(
            array(
                'partno'  => 10,
                'name'    => 8,
                'details' => 1
            )
        )
        ->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_EXTENDED)
        ->setSortMode(\Sphinx\SphinxClient::SPH_SORT_EXTENDED, "@weight DESC")
        ->get(true);  //passing true causes get() to respect returned sort order

## Integration with Eloquent

This package integrates well with Eloquent. You can change index configuration with `modelname` to get Eloquent's Collection (Illuminate\Database\Eloquent\Collection) as a result of `SphinxSearch::search`.

    return array (
        'host'    => '127.0.0.1',
        'port'    => 9312,
        'default_search' => 'default',

        'searches' => array (
            'default' => array(
                'indexes' => array('main','delta'),
                'mapping' => array(
                    'model'  => 'Keyword',
                    'column' => 'id'
                )
            ),
        )
    );


## Paging results in Laravel 4 (with caching)

```php
Route::get('/search', function ()
{
    $page = Input::get('page', 1);
    $search = Input::get('q', 'search string');    
    $perPage = 15;  //number of results per page
    // use a cache so you dont have to keep querying sphinx for every page!
    $results = Cache::remember(Str::slug($search), 10, function () use($search)
    {
        return SphinxSearch::search($search)
        ->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_EXTENDED2)        
        ->get();
    });
    if ($results) {
        $pages = array_chunk($results, $perPage);
        
        $paginator = Paginator::make($pages[$page - 1], count($results), $perPage);
        return View::make('searchpage')->with('data', $paginator);
    }
    return View::make('notfound');
});
```
And, in your view after you finish displaying rows,
```php
<?php echo $data->links()?>
```

## Searching through multiple Sphinx indexes (main/delta)

It is a common strategy to utilize the main+delta scheme (www.sphinxconsultant.com/sphinx-search-delta-indexing/). When using deltas, it is often necessary to query on multiple indexes simultaneously. In order to achieve this using SphinxSearch, modify your config file to include the "name" and "mapping" keys like so:

```php

    return array (
        'host'    => '127.0.0.1',
        'port'    => 9312,
        'default_search' => 'default',

        'searches' => array (
            'default' => array(
                'indexes' => array('main','delta'),
                'mapping' => array(
                    'model'  => 'Keyword',
                    'column' => 'id'
                )
            ),
        )
    );
```

