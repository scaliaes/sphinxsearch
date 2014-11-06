<?php

return array (
	'host'    => '127.0.0.1',
	'port'    => 9312,
    'timeout' => 30,
	'indexes' => array (
		'my_index_name' => array ( 'table' => 'keywords', 'column' => 'id' ),
	)
);
