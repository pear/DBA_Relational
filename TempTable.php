<?php
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jacob Lee <jacobswell4u@yahoo.com>                          |
// +----------------------------------------------------------------------+
//
// $Id$
//

/**
 * Class for DBA Temporary Table Object
 *
 * Class name : DBA_TempTable
 */
class DBA_TempTable
{
	// {{{ instance variables
    /**
     * Contents of the temporary table
     */
	var $_rows;
	// }}}

	// {{{ function DBA_TempTable($rows=null, $alias=null)
	function DBA_TempTable($rows=null, $alias=null)
	{
		if (!is_null($rows) && is_array($rows))
			$this->setRows(&$rows, $alias);
	}
	// }}}

	// {{{ function isTempTable($tTable)
	function isTempTable($tTable)
	{
		return (is_object($tTable) &&
            (get_class($tTable) == 'dba_temptable' ||
			is_subclass_of($tTable, 'dba_temptable')));
	}
	// }}}

	// {{{ function setRows($rows, $alias=null)
	function setRows($rows, $alias=null)
	{
		if (is_array($rows)) {
			$v_rows = array();
			
			foreach ($rows as $row) {
				foreach($row as $field=>$data) {
					if ($field == '_rowid' || $field == '_timestamp')
						continue;
					if (is_string($alias) && strlen($alias))
						$v_row[$alias.'.'.$field] = $data;
					else $v_row[$field] = $data;
				}
				$v_rows[] = $v_row;
			}
			$this->_rows = &$v_rows;
		}
	}
	// }}}

	// {{{ function blankRow()
	function blankRow()
	{
		$curRow = current($this->_rows);
		if ($curRow === false) {
			reset($this->_rows);
			$curRow = current($this->_rows);
		}

		foreach ($curRow as $field=>$data) {
			if ($field{0} == '_') // ignore system rows
				continue;
			$blank[$field] = null;
		}
		return $blank;
	}
	// }}}

	// {{{ function firstRow()
	function firstRow()
	{
		$result = reset($this->_rows);
		if ($result === false) return $result;
		return key($this->_rows);
	}
	// }}}

	// {{{ function nextRow()
	function nextRow()
	{
		$result = next($this->_rows);
		if ($result === false) return $result;
		return key($this->_rows);
	}
	// }}}

	// {{{ function getRow($key)
	function getRow($key)
	{
		foreach ($this->_rows[$key] as $field=>$data) {
			if ($field{0} == '_') // ignore system rows
				continue;
			$row[$field] = $data;
		}
		return $row;
	}
	// }}}

	// {{{ function &getRows()
	function &getRows()
	{
		return $this->_rows;
	}
}

?>
