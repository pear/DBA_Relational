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
// | Authors: Brent Cook <busterb@mail.utexas.edu>                        |
// |          Jacob Lee <jacobswell4u@yahoo.com>                          |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'PEAR.php';
require_once 'DBA/Table.php';
require_once 'DBA/TempTable.php';

/**
 * A relational database manager using DBA_Table as a storage object.
 * DBA_Relational extends DBA_Table by providing uniform access to multiple
 * tables, automatically opening, closing and locking tables as needed by
 * various operations, and provides a join operation.
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @package DBA
 * @access  public
 * @version 0.19
 */
class DBA_Relational extends PEAR
{
    // {{{ instance variables
    /**
     * Handles to table objects
     * @access private
     * @var array
     */
    var $_tables=array();

    /**
     * Location of table data files
     * @access private
     * @var string
     */
    var $_home;

    /**
     * Table driver to use
     * @access private
     * @var string
     */
    var $_driver;

    // }}}

    // {{{ DBA_Relational($home = '', $driver = 'file')
    /**
     * Constructor
     *
     * @param string  $home path where data files are stored
     * @param string  $driver DBA driver to use
     */
    function DBA_Relational($home = '', $driver = 'file')
    {
        // call the base constructor
        $this->PEAR();

        // add trailing slash to home if not present
        if (substr($home, -1) != '/') {
            $home = $home.'/';
        }
        $this->_home = $home;

        $this->_driver = $driver;

        // create the _tables table. this keeps track of the tables to be used
        // in DBA_Relational as well as which driver to use for each.
        if (!$this->tableExists('_tables')) {
            $this->createTable('_tables', 
                array('name'=>array('type'=>'varchar',
                                    'primary_key'=>true,
                                    'size'=>20),
                      'driver'=>array('type'=>'varchar'),
                      'description'=>array('type'=>'text')));
        }

        // create the _sequences table. this keeps track of named sequences
        if (!$this->tableExists('_sequences')) {
            $this->createTable('_sequences', 
                array('name'=>array('type'=>'varchar',
                                    'primary_key'=>true,
                                    'size'=>20),
                      'value'=>array('type'=>'integer'),
                      'increment'=>array('type'=>'integer')));
        }
    }
    // }}}

    // {{{ raiseError($message)
    function raiseError($message) {
        return PEAR::raiseError('DBA_Relational: '.$message);
    }
    // }}}

    // {{{ close()
    /**
     * Closes all open tables
     *
     * @access public
     */
    function close()
    {
        if (sizeof($this->_tables)) {
            reset($this->_tables);
            $this->_tables[key($this->_tables)]->close();
            while(next($this->_tables)) {
                $this->_tables[key($this->_tables)]->close();
            }
        }
    }
    // }}}

    // {{{ _DBA_Relational()
    /**
     * PEAR emulated destructor calls close on PHP shutdown
     * @access  private
     */
    function _DBA_Relational()
    {
        $this->close();
    }
    // }}}

    // {{{ _openTable($tableName, $mode = 'r')
    /**
     * Opens a table, keeps it in the list of tables. Can also reopen tables
     * to different file modes
     *
     * @access  private
     * @param   string $tableName name of the table to open
     * @param   char   $mode      mode to open the table; one of r,w,c,n
     * @return  object PEAR_Error on failure
     */
    function _openTable($tableName, $mode = 'r')
    {
        if (!isset($this->_tables[$tableName])) {
            if (!$this->tableExists($tableName)) {
                return $this->raiseError('table '.$tableName.' does not exist');
            } else {
                $this->_tables[$tableName] =& new DBA_Table($this->_driver);
            }
        }

        if (!$this->_tables[$tableName]->isOpen()) {
            return $this->_tables[$tableName]->open($this->_home.$tableName, $mode);
        } else {
            if (($mode == 'r') && !$this->_tables[$tableName]->isReadable()) {
                // obtain a shared lock on the table
                return $this->_tables[$tableName]->lockSh();
            } elseif (($mode == 'w') &&
                       !$this->_tables[$tableName]->isWritable()){
                // obtain an exclusive lock on the table
                return $this->_tables[$tableName]->lockEx();
            }
        }
    }
    // }}}

    // {{{ tableExists($tableName)
    /**
     * Returns whether the specified table exists in the db home
     *
     * @param   string $tableName table to check existence of
     * @return  boolean true if the table exists, false if it doesn't
     */
    function tableExists($tableName)
    {
        return DBA::db_exists($this->_home.$tableName, $this->_driver);
    }
    // }}}

    // {{{ createTable
    /**
     * Creates a new table
     *
     * @access  public
     * @param   string $tableName   name of the table to create
     * @param   array  $schema field schema for the table
     * @param   string $driver driver to use for this table
     * @return  object PEAR_Error on failure
     */
    function createTable($tableName, $schema, $driver=null)
    {
        if (is_null($driver)) {
            $driver = $this->_driver;
        }
        $this->insert('_tables', array($tableName, $driver));
        return DBA_Table::create($this->_home.$tableName, $schema, $driver);
    }
    // }}}

    // {{{ dropTable
    /**
     * Deletes a table permanently
     *
     * @access  public
     * @param   string  $tableName name of the table to delete
     * @param   string  $driver driver that created the table
     * @return  object  PEAR_Error on failure
     */
    function dropTable($tableName, $driver=null)
    {
        if (is_null($driver)) {
            $driver = $this->_driver;
        }
        if (isset($this->_tables[$tableName])) {
            if (PEAR::isError($result = $this->_tables[$tableName]->close())) {
                return $result;
            }
            unset($this->_tables[$tableName]);
        }

        return DBA::db_drop($tableName, $driver);
    }
    // }}}
    
    // {{{ getSchema
    /**
     * Returns an array with the stored schema for the table
     *
     * @param   string $tableName
     * @return  array
     */
    function getSchema($tableName)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->getSchema();
        }
    }
    // }}}

    // {{{ isOpen($tableName)
    /**
     * Returns the current read status for the database
     *
     * @return  boolean
     */
    function isOpen($tableName)
    {
        if (isset($this->_tables[$tableName])) {
            return $this->_tables[$tableName]->isOpen();
        } else {
            return false;
        }
    }
    // }}}

    // {{{ insert($tableName, $data)
    /**
     * Inserts a new row in a table
     *
     * @param   string $tableName table on which to operate
     * @param   array  $data assoc array or ordered list of data to insert
     * @return  mixed  PEAR_Error on failure, the row index on success
     */
    function insert($tableName, $data)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->insert($data);
        }
    }
    // }}}

    // {{{ replace($tableName, $rawQuery, $data, $rows=null)
    /**
     * Replaces rows that match $rawQuery
     *
     * @access  public
     * @param   string $rawQuery query expression for performing the replace
     * @param   array  $rows subset of rows to choose from
     * @return  object PEAR_Error on failure
     */
    function replace($tableName, $rawQuery, $data, $rows=null)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->replace($rawQuery, $data, $rows);
        } 
    }
    // }}}

    // {{{ replaceKey($tableName, $key, $data)
    /**
     * Replaces an existing row in a table, inserts if the row does not exist
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $key row id to replace
     * @param   array  $data assoc array or ordered list of data to insert
     * @return  mixed  PEAR_Error on failure, the row index on success
     */
    function replaceKey($tableName, $key, $data)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->replaceKey($key, $data);
        } 
    }
    // }}}

    // {{{ remove($tableName, $rawQuery, $rows=null)
    /**
     * Removes rows that match $rawQuery with $
     *
     * @access  public
     * @param   string $rawQuery query expression for performing the remove
     * @param   array  $rows subset of rows to choose from
     * @return  object PEAR_Error on failure
     */
    function remove($tableName, $rawQuery, $rows=null)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->remove($rawQuery, $rows);
        } 
    }
    // }}}

    // {{{ removeKey($tableName, $key)
    /**
     * Remove an existing row in a table
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $key row id to remove
     * @return  object PEAR_Error on failure
     */
    function removeKey($tableName, $key)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->remove($key);
        }
    }
    // }}}

    // {{{ fetch($tableName, $key)
    /**
     * Fetches an existing row from a table
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $key row id to fetch
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function fetch($tableName, $key)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->fetch($key);
        }
    }
    // }}}

    // {{{ sort($fields, $order='a', $rows)
    /**
     * Sorts rows by field in either ascending or descending order
     * SQL analog: 'select * from rows, order by fields'
     *
     * @access  public
     * @param   mixed  $fields a string with the field name to sort by or an
     *                         array of fields to sort by in order of preference
     * @param   string $order 'a' for ascending, 'd' for descending
     * @param   array  $rows rows to sort, sorts the entire table if not
     *                       specified
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function sort($fields, $order='a', $rows)
    {
        return DBA_Table::sort($fields, $order, $rows);
    }
    // }}}

    // {{{ project($fields, $rows)
    /**
     * Projects rows by field. This means that a subset of the possible fields
     * are in the resulting rows. The SQL analog is 'select fields from table'
     *
     * @access  public
     * @param   array  $fields fields to project
     * @param   array  $rows rows to project, projects entire table if not
     *                       specified
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function project($fields, $rows)
    {
        return DBA_Table::project($fields, $rows);
    }
    // }}}

    // {{{ unique($rows)
    /**
     * Returns the unique rows from a set of rows
     *
     * @access  public
     * @param   array  $rows rows to process, uses entire table if not
     *                     specified
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function unique($rows)
    {
        return DBA_Table::unique($rows);
    }
    // }}}

    // {{{ finalize($tableName, $rows=null)
    /**
     * Converts the results from any of the row operations to a 'finalized'
     * display-ready form. That means that timestamps, sets and enums are
     * converted into strings. This obviously has some consequences if you plan
     * on chaining the results into another row operation, so don't call this
     * unless it is the final operation.
     *
     * This function does not yet work reliably with the results of a join
     * operation, due to a loss of metadata
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   array  $rows rows to finalize, if none are specified, returns
     *                      the whole table
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function finalize($tableName, $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->finalize($rows);
        }
    }
    // }}}

    // {{{ _validateTable(&$table, &$rows, &$fields, $altName)
    /**
     * Verifies that the fields submitted exist in $table
     * @access private
     */
    function _validateTable(&$table, &$rows, &$fields, $altName)
    {
        // validate query by checking for existence of fields
        if (is_string($table) && !PEAR::isError($this->_openTable($table, 'r')))
        {

            $rows = $this->_tables[$table]->getRows();
            $fields = $this->_tables[$table]->getFieldNames();
            return true;

        } elseif (is_array($table) && sizeof($table)) {
            reset($table);
            $rows = $table;
            $fields = array_keys(current($table));
            $table = $altName;
            return true;
        } elseif (is_null($table)) {
            $fields = null;
            $rows = null;
            return true;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ select($tableName, $query, $rows=null)
    /**
     * Performs a select on a table. This means that a subset of rows in a
     * table are filtered and returned based on the query. Accepts any valid
     * expression of the form '(field == field) || (field > 3)', etc. Using the
     * expression '*' returns the entire table
     * SQL analog: 'select * from rows where rawQuery'
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $query query expression for performing the select
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function select($tableName, $query='*', $rows=null)
    {
        $result = $this->_openTable($tableName,'r');
        if (PEAR::isError($result)) {
            return $result;
        }

        // use the table's select query parser
        return $this->_tables[$tableName]->select($query, $rows);
    }
    // }}}

    // {{{ join($tableA, $tableB, $rawQuery)
    /**
     * Joins rows between two tables based on a query.
     *
     * @access  public
     * @param   string $tableA   name of table to join
     * @param   string $tableB   name of table to join
     * @param   string $rawQuery expression of how to join tableA and tableB
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function join($tableA, $tableB, $rawQuery)
    {
        return $this->raiseError('TODO: merge new join()');
/*
        // validate tables
        if (!$this->_validateTable($tableA, $rowsA, $fieldsA, 'A')) {
            return $this->raiseError("$tableA not in query");
        }

        if (!$this->_validateTable($tableB, $rowsB, $fieldsB, 'B')) {
            return $this->raiseError("$tableA not in query");
        }

        // check for empty tables
        if (is_null($rowsA) || is_null($rowsB)) {
            return null;
        }
        
        // TODO Implement merge join, needs secondary indexes on tables
        // build the join operation with nested loops
        $query = $this->_parsePHPQuery($rawQuery, $fieldsA, $fieldsB,
                                       $tableA, $tableB);
        if (PEAR::isError($query)) {
            return $query;
        }

        $results = array();
        $PHPJoin = 'foreach ($rowsA as $rowA) foreach ($rowsB as $rowB) if ('.
          $this->_parsePHPQuery($rawQuery, $fieldsA, $fieldsB, $tableA, $tableB)
          .') $results[] = array_merge($rowA, $rowB);';

        // evaluate the join
        eval ($PHPJoin);

        return $results;
*/
    }
    // }}}
}
