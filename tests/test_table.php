<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2003 Brent Cook                                        |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Author: Brent Cook <busterb@mail.utexas.edu>                         |
// +----------------------------------------------------------------------+
//
// $Id$
//

// test functionality of the dba table layer

require_once 'DBA/Table.php';
require_once 'DBA/Toolbox.php';
require_once 'hatSchema.php';

$table =& new DBA_Table();

$table->_dateFormat = "M j, Y";

$result = DBA_Table::create('hats', $hatTableStruct, 'file');
if (PEAR::isError($result)) {
    echo $result->getMessage()."\n";
    exit;
}

// open and close table multiple times while inserting
//foreach (array('w','r') as $mode) {
$mode = 'w';
$result = $table->open('hats', $mode);
if (PEAR::isError($result)) {
    echo $result->getMessage()."\n";
    exit;
}

echo "Created table 'hats'\n";

for ($i=0; $i < 2; ++$i) {
    foreach ($hats as $hat) {
        $result = $table->insert($hat);
        if (PEAR::isError($result)) {
            echo 'Could not insert: '.$result->getMessage()."\n";
        }
    }
}

$queries = array(
                '$table->select("(type != bowler) and (type != fedora)")',
                '$table->select("quantity<=60")',
                '$table->select("quantity>=50")',
                '$table->sort("quantity, hat_id", "a", $table->select("*"))',
                '$table->sort(array("quantity", "hat_id"), "a", $table->getRows())',
                '$table->sort("lastshipment", "d", $table->select("*"))',
                '$table->unique($table->project("brand,quantity,  type",$table->sort("quantity", "d", $table->select("type != \'top hat\'"))))',
                '$table->remove("type == bowler")',
                '$table->project("brand, type", $table->select("*"))',
                );

foreach ($queries as $query) {
    echo "Query: $query\n";
    eval ('$results = '.$query.';');

    if (PEAR::isError($results)) {
        echo " Query failed.\n";
        echo $results->getMessage()."\n";
    } else {
        echo formatTextTable($table->finalize($results), null, 'mysql');
    }
    echo "------------------------------------------------\n\n";
}
$table->close();
//}

?>
