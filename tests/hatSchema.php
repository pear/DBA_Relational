<?php
	$hatTableStruct = array (
	                   'type' => array ('type' => 'enum',
	                                    'domain' => array ('fedora',
	                                                       'stocking cap',
	                                                       'top hat',
	                                                       'bowler',
														   'beanie'),
	                                    'primary_key' => True,
	                                    'default' => 'fedora'),

	                   'quantity' => array ('type' => 'integer',
	                                        'default' => 0),

	                   'brand' => array ('type' => 'varchar',
	                                     'default' => 'No Name',
	                                     'size' => 45),

	                   'sizes' => array ('type' => 'set',
	                                     'domain' => array ('small',
	                                                        'medium',
	                                                        'large',
	                                                        'xlarge'),
	                                     'not_null' => true),

	                   'lastshipment' => array ('type' => 'timestamp'),
	                   'hat_id' => array ('type' => 'integer',
	                                      'auto_increment' => True,
	                                      'primary_key' => True,
                                          'default' => 100)
	                  );

	$hats = array(
        array ('type' => 'bowler',
	           'quantity' => 20,
	           'brand' => "Jill's Hats",
	           'sizes' => array ('small', 'large'),
	           'lastshipment' => 'May 29, 1978'),

	    array ('type' => 'stocking cap',
	           'sizes' => array ('xlarge', 'large'),
	           'brand' => "Brent's Hats",
   	           'lastshipment' => time()),

	    array ('type' => 'fedora',
	           'quantity' => 800,
	           'brand' => "Shilanda's Hats",
	           'sizes' => array ('small', 'medium', 'large'),
	           'lastshipment' => 'June 30, 2001'),

	    array ('type' => 'top hat',
	           'quantity' => 10,
	           'brand' => "Laurie's Hats",
	           'sizes' => array ('small', 'medium', 'large'),
	           'lastshipment' => 'April 22, 2000'),

	    array ('type' => 'top hat',
	           'quantity' => 60,
	           'brand' => "Travis' Hats",
	           'sizes' => array ('small'),
	           'lastshipment' => 'January 1, 2000'),

	    array ('type' => 'beanie',
	           'quantity' => 600,
	           'brand' => "Elizabeth's Hats",
	           'sizes' => array ('small', 'large'),
	           'lastshipment' => 'May 18, 2002'));

?>
