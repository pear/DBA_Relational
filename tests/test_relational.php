<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Brent Cook                                        |
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

ini_set('include_path',ini_get('include_path').':../../');
require_once 'PEAR.php';
require_once 'DBA/Relational.php';
require_once 'DBA/Toolbox.php';
require_once 'empSchema.php';

// set the working directory and driver
$db =& new DBA_Relational('./', 'file');

// generate and populate the tables
foreach ($empSchema as $tableName=>$tableSchema) {
    $data = "{$tableName}_data";

    echo "Creating table: $tableName\n";

    $result = $db->createTable($tableName, $tableSchema);

    if (PEAR::isError($result)) {
        echo $result->getMessage()."\n";
    } else {
        foreach ($empData[$tableName] as $row) {
            $result = $db->insert($tableName, $row);
            if (PEAR::isError($result)) {
                echo $result->getMessage()."\n";
            }
            // exercise sequences (autoincrement)
            $db->close();
        }
    }
}

// closes all open tables - testing auto-open feature
$db->close();

$queries = array(
'$db->select("emp", "*")',
'$db->select("deptloc", "*")',
'$db->select("location", "*")',
'$db->select("dept", "*")',
'$db->select("account", "*")',
'$db->select("nothere", "pigs == \'fly\'")',
'$db->select("emp", "salary >= 1500")',

'$db->sort("empname", "a",
    $db->select("emp", "(job != \'analyst\') and (job != \'intern\')")
    )',

'$db->sort("empname", "d",
    $db->select("emp", "(job != \'analyst\') and (job != \'intern\')")
    )',

'$db->project("empname, deptname, deptno",
    $db->join("emp", "dept", "emp.deptno == dept.deptno")
    )',

'$db->join("location", 
    $db->join("dept", "deptloc", "dept.deptno == deptloc.deptno"),
        "location.locno == B.locno"
    )',

'$db->sort("manager", "a",
    $db->join("location",
        $db->join("dept", "deptloc", "dept.deptno == deptloc.deptno"),
            "location.locno == B.locno"
        )
    )',

'$db->sort("empname, locname, deptname", "a",
    $db->project("empname, locname, deptname",
        $db->join("emp", 
            $db->join("location",
                $db->join("dept", "deptloc", "dept.deptno == deptloc.deptno"),
                    "location.locno == B.locno"
                ),
                "emp.id != B.manager"
            )
        )
    )'
);

$sql_queries = array(
'SELECT * FROM emp',
'SELECT * FROM deptloc',
'SELECT * FROM location',
'SELECT * FROM dept',
'SELECT * FROM account',
'SELECT * FROM nothere WHERE pigs = "fly"',
'SELECT * FROM emp
WHERE salary >= 1500',
'SELECT * from emp
WHERE (job <> "analyst") AND (job <> "intern")
ORDER BY empname',
'SELECT * from emp
WHERE (job <> "analyst") AND (job <> "intern")
ORDER BY empname DESC',
'SELECT empname, deptname, deptno FROM emp, dept
WHERE emp.deptno = dept.deptno',
'SELECT * FROM location, dept, deptloc
WHERE dept.deptno = deptloc.deptno AND location.locno = deptloc.locno',
'SELECT * FROM location, dept, deptloc
WHERE dept.deptno = deptloc.deptno AND location.locno = deptloc.locno
ORDER BY manager',
'SELECT empname, locname, deptname FROM location, dept, deptloc, emp
WHERE dept.deptno = deptloc.deptno AND location.locno = deptloc.locno
AND emp.id <> dept.manager
ORDER BY empname, locname, deptname'
);

foreach ($queries as $key=>$query) {
    echo "Query:\n$query\n\n";
    echo "SQL equivalent:\n$sql_queries[$key]\n\n";
    eval ('$results = '.$query.';');

    if (PEAR::isError($results)) { 
        echo " Query failed.\n";
        echo $results->getMessage()."\n";
    } else {
        echo "Results:\n";
        echo formatTextTable($results);
    }

    echo "\n************************************************\n\n";
}
	
//	$db->close();
?>
