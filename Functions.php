
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2003 Brent Cook                                        |
// | Copyright (c) 2002 The PHP Group
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
// | Authors: Jacob Lee <jacobswell4u@yahoo.com>                          |
// | Authors: Brent Cook <busterb@mail.utexas.edu>                         |
// +----------------------------------------------------------------------+
//
// $Id$
//
/**
 * functions like those in SQL
 */

class DBA_Functions
{
    // {{{ max($field, $rows)
    function max($field, $rows)
    {
        if (is_null($rows) || !sizeof($rows)) return null;
        return max(DBA_Table::_getColumn($field, $rows));
    }
    // }}}

    // {{{ min($field, $rows)
    function min($field, $rows)
    {
        if (is_null($rows) || !sizeof($rows)) return null;
        return min(DBA_Table::_getColumn($field, $rows));
    }
    // }}}

    // {{{ sum($field, $rows)
    function sum($field, $rows)
    {
        if (is_null($rows) || !sizeof($rows)) return null;
        return array_sum(DBA_Table::_getColumn($field, $rows));
    }
    // }}}

    // {{{ avg($field, $rows)
    function avg($field, $rows)
    {
        if (is_null($rows) || !sizeof($rows)) return null;
        return array_sum(DBA_Table::_getColumn($field, $rows)) / sizeof($rows);
    }
    // }}}

    // {{{ count($field, $rows)
    function count($rows)
    {
        if (is_null($rows) || !sizeof($rows)) return null;
        return count($rows);
    }
    // }}}

    // {{{ time()
    /**
     * Internal function for returning the current time
     *
     * @access private
     */
    function time() {
        return time();
    }
    // }}}

	// {{{ in()
	function in($val, $val1, $val2, $val3 = null)
	{
		$isIn = ($val == $val1 or $val == $val2) ? true : false;
		if ($val3 == null) return $isIn;
		return ($isIn or $val == $val3) ? true : false;
	}
	// }}}

	// {{{ between($num, $min, $max)
	function between($num, $min, $max)
	{
		if ($min == $max) return ($num == $min) ? true : false;
		if ($min > $max) {
			$tmp = $max;
			$max = $min;
			$min = &$tmp;
		}
		return ($num >= $min and $num <= $max) ? true : false;
	}
	// }}}

	// {{{ log($val, $base)
	function log($val, $base)
	{
		return log10($val)/log10($base);
	}
	// }}}

	// {{{ mod($val, $devider)
	function mod($val, $devider)
	{
		if ($devider == null) return null;
		return $val % $devider;
	}
	// }}}

	// {{{ sign($val)
	function sign($val)
	{
		return ($val > 0) ? 1 : (($val == 0) ? 0 : -1);
	}
	// }}}

	// {{{ concat($str1, $str2)
	function concat($str1, $str2)
	{
		return $str1.$str2;
	}
	// }}}

	// {{{ initcap($str)
	function initcap($str)
	{
		return ucfirst($str);
	}
	// }}}

	// {{{ lpad($input, $padLength, $padString)
	function lpad($input, $padLength, $padString)
	{
		return str_pad($input, $padLength, $padString, STR_PAD_LEFT);
	}
	// }}}

	// {{{ rpad($input, $padlength, $padString)
	function rpad($input, $padlength, $padString)
	{
		return str_pad($input, $padLength, $padString, STR_PAD_RIGHT);
	}
	// }}}

	// {{{ replace($str, $search, $replace = '')
	function replace($str, $search, $replace = '')
	{
		if ($search == null) return $str;
		if ($replace == null) $replace = '';
		return str_replace($search, $replace, $str);
	}
	// }}}

	// {{{ substring($str, $start, $length = null)
	function substring($str, $start, $length = null)
	{
		if ($start > 0) $start--;
		return ($length == null)	? substr($str, $start) 
									: substr($str, $start, $length);
	}
	// }}}

	// {{{ translate($str, $from, $to)
	function translate($str, $from, $to)
	{
		if ( strlen($from) != strlen($to) )
			return PEAR::raiseError("needs elements that have the same string length");
		$fromWord = explode('', $from);
		$fromWord = array_flip($fromWord);
		$toWord = explode('', $to);
		$translate = '';
		for ($i = 0; $i < strlen($str); $i++) {
			$translate .= $toWord[$fromWord[$str[$i]]];
		}
		return $translate;
	}
	// }}}

	// {{{ instr($str, $match, $start = 1, $count = 1)
	function instr($str, $match, $start = 1, $count = 1)
	{
		if ($start > 0) {
			$start--;
			$pos = strpos($str, $match, $start);
			for ($i = 1; $i != $count; $i++) {
				$pos = strpos($str, $match, $pos+1);
				if ($pos == false) return 0;
			}
			return $pos+1;
		}elseif ($start < 0) {
			$pos = strrpos($str, $match);
			for ($i = 1; $i != $count; $i++) {
				$pos = strrpos(substr($str, 0, $pos), $match);
				if ($pos == false) return 0;
			}
			return $pos+1;
		}
		return PEAR::raiseError("invalid start number");
	}
	// }}}

	// {{{ strlike($str,$search,$option=0)
	function strlike($str,$search,$option=0)
	{
		/**
		* if the value of $option is 
		*     0 : CASE SENSITIVE
		*     1 : CASE INSENSITIVE
		*     2 : LIKE PREG_MATCH
		*/
		if ($option<2) {
			$search = quotemeta($search);
			$search = preg_replace("/^\\%/", ".*", $search);
			$search = preg_replace("/\\%$/", ".*", $search);
			$search = preg_replace("/_/", "?", $search);
		}
		switch ($option) {
			case 0:
				return preg_match("/^$search$/s", $str);
			case 1:
				return preg_match("/^$search$/si", $str);
			case 2:
				return preg_match("/$search/is", $str);
		}
	}
	// }}}
}
?>
