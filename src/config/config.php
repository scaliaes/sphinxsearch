<?php

return array (

    /*
    |--------------------------------------------------------------------------
    | Connection Info
    |--------------------------------------------------------------------------
    | The host and port for the SphinxSearch Server
    */
	'host'    => '127.0.0.1',
	'port'    => 9312,

    /*
    |--------------------------------------------------------------------------
    | Default search
    |--------------------------------------------------------------------------
    | The default search to perform
    */
    'default_search' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Searches
    |--------------------------------------------------------------------------
    | Searches with config info
    |
    */
    'searches' => array (
        /*
        |--------------------------------------------------------------------------
        | searches format
        |--------------------------------------------------------------------------
        |
        | 	'my_search_name' => array (            //unique search name
        |        'indexes' => array('index'),      //list of indexes to search
        |        'mapping' => array(
        |            ['table' => 'keyword',        //the table to query matched ids from,requires column set too
        |             'column' => 'id']            //the column to match ids against, requires table to be set
        |            ['model' => 'Keyword',        //the Eloquent model to query from
        ]             'column' => 'id']            //the column to match the Eloquent model on
        |        )
        |   )
        */

        /*
        |--------------------------------------------------------------------------
        | Examples
        |--------------------------------------------------------------------------
        */
        //will search main,delta and return a result from the database
        'default' => array(
            'indexes' => array('main','delta'),
            'mapping' => array(
                'table'  => 'keyword',
                'column' => 'id'
            )
        ),

        //will search default_index and return a Eloquent query result
        'default_2' => array(
            'indexes' => array('default_index'),
            'mapping' => array(
                'model'  => 'Keyword',
                'column' => 'id'
            )
        ),

        //will return ids
        'default_3' => array(
            'indexes' => array('default_3')
        )
	)
);
