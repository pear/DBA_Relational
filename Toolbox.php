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
//
// $Id$
//
require_once 'PEAR.php';

/**
 * Insert widgets into a quickform object suitable for updating a row in a DBA
 * table.
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @version 0.19
 * @access  public
 * @package DBA
 */
function dbaToQuickform(&$form, $schema, $auxMeta)
{
    foreach ($schema as $name=>$meta) {
        if (isset($auxMeta['default'])) {
            $defaults[$name] = $auxMeta['default'];
        }
        if (isset($auxMeta[$name])) {
            $desc = isset($auxMeta[$name]['desc']) ?
                        $auxMeta[$name]['desc'] : $name;
            switch($meta[DBA_TYPE]) {
                case DBA_INTEGER:
                    if (isset($auxMeta[$name]['min']) &&
                        isset($auxMeta[$name]['max'])) {
                        $form->addElement('select', $name, $desc,
                                          range($auxMeta[$name]['min'],
                                          $auxMeta[$name]['max']));
                    } else {
                        $form->addElement('text', $name, $desc,
                                          array('size'=>4, 'maxlength'=>4));
                    }
                    break;
                case DBA_VARCHAR:
                    if ($meta[DBA_SIZE] <= 60) {
                        $form->addElement('text', $name, $desc,
                                        array('size'=>$meta[DBA_SIZE]));
                    } else {
                        $form->addElement('textarea', $name, $desc,
                                array('rows'=>4, 'wrap'=>'soft', 'cols'=>45));
                    }
                    break;
                case DBA_BOOLEAN:
                    $form->addElement('select', $name, $desc,
                                      array('yes'=>'Yes', 'no'=>'No'));
                    break;
                case DBA_TEXT:
                    $form->addElement('textarea', $name, $desc,
                                      array('rows'=>4, 'wrap'=>'soft', 'cols'=>45));
                    break;
            }
        }
    }
}

/**
 * Postprocess $_POST variables that were left by a form using addQuickformDBA
 * @return array DBA row suitable for inserting into a DBA table
 */
function quickformToDBA($schema, $auxMeta)
{
    foreach ($schema as $name=>$meta) {
        if (isset($auxMeta[$name]) && isset($_POST[$name])) {
            if (($meta[DBA_TYPE] == DBA_INTEGER) &&
                isset($auxMeta['min'])) {
                $data[$name] = $_POST[$name] - $auxMeta['min'];
            } else {
                $data[$name] = $_POST[$name];
            }
        }
    }
    return $data;
}

/**
 * Generates a text table from a results set, a-la MySQL
 *
 * @param   array $rows
 * @param   array $fields list of fields to display
 * @param   string $style style to display table in; 'oracle', 'mysql'
 * @return  string text-formatted results set
 */
function formatTextTable($rows, $fields = null, $style = 'oracle')
{
    $corner = ($style == 'oracle') ? ' ' : '+';
    $wall = ($style == 'oracle') ? ' ' : '|';

    if (is_array($rows) && sizeof($rows)) {

        if (is_null($fields)) {
            $fields = array_keys(current($rows));
        }

        // get the maximum length of each field
        foreach ($fields as $key=>$field) {
            $longest[$key] = strlen($field) + 1;
            foreach ($rows as $row) {
                $rowLen = strlen($row[$field]) + 1;
                if ($rowLen > $longest[$key]) {
                    $longest[$key] = $rowLen;
                }
            }
        }

        // generate separator line
        foreach ($longest as $length) {
            $separator .= "$corner-".str_repeat('-',$length);
        }
        $separator .= "$corner\n";

        $buffer = ($style == 'oracle') ? '' : $separator;

        // print fields
        foreach ($fields as $key=>$field) {
            $buffer .= "$wall ".str_pad($field, $longest[$key]);
        }
        $buffer .= "$wall\n$separator";

        // print rows
        foreach ($rows as $row) {
            foreach ($fields as $key=>$field) {
                $buffer .= "$wall ".str_pad($row[$field],
                        $longest[$key]);
            }
            $buffer .= "$wall\n";
            $buffer .= ($style == 'oracle') ? '' : $separator;
        }
    }
    return $buffer;
}

/**
 * Generates an HTML table from a results set
 *
 * This function uses custom CSS classes to define the table style.
 * A recommended CSS is as follows:
 *
 * .dbatable {
 *   border: solid;
 *   border-color: gray;
 *   border-collapse: collapse;
 * }
 * .dbatablefield {
 *   font-weight: bold;
 *   text-align: center;
 *   color: black;
 *   background-color: #CCCCFF;
 *   border: solid;
 *   border-color: gray;
 *   border-width: thin;
 * }
 * .dbatablerow0 {
 *   font-weight: normal;
 *   color: black;
 *   background-color: #FFFFFF;
 *   border: solid;
 *   border-color: gray;
 *   border-width: thin;
 * }
 * .dbatablerow1 {
 *   font-weight: normal;
 *   color: black;
 *   background-color: #F4F4F4;
 *   border: solid;
 *   border-color: gray;
 *   border-width: thin;
 * }
 *
 * @param   array $rows
 * @param   array $fields list of fields to display
 * @return  string HTML-formatted results set
 */
function formatHtmlTable($rows, $fields = null)
{
    if (is_numeric($rows) or is_string($rows)) return "$rows<br>";
    if (is_array($rows) && sizeof($rows)) {
        if (is_null($fields)) {
            $fields = array_keys(current($rows));
        }
        $buffer = "<table class=\"dbatable\">\n";

        // print fields
        $buffer .= "  <tr>\n";
        foreach ($fields as $field) {
            $buffer .= "    <th class=\"dbatablefield\">&nbsp;&nbsp;"
                       ."$field&nbsp;&nbsp;</th>\n";
        }
        $buffer .= "  </tr>\n";

        // print rows
        $rowStyle = 0;
        foreach ($rows as $row) {
            $buffer .= "  <tr>\n";
            foreach ($fields as $field) {
                $buffer .= "    <td class=\"dbatablerow$rowStyle\">&nbsp;&nbsp;"
                           .$row[$field]."&nbsp;&nbsp;</td>\n";
            }
            $rowStyle = 1 - $rowStyle;
            $buffer .= "  </tr>\n";
        }

        $buffer .= "</table>\n";
    }
    return $buffer;
}
?>
