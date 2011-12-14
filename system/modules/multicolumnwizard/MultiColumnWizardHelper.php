<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2011, certo web & design GmbH 2011, MEN AT WORK 2011
 * @package    MultiColumnWizard 
 * @license    LGPL 
 * @filesource
 */

class MultiColumnWizardHelper extends System
{

    /**
     * Static helper method to get all the data from a certain key for all the rows
     * @param string
     * @param string
     * @return array
     */
    public static function getByKey($strSerialized, $strKey)
    {
        $arrData = deserialize($strSerialized);
        $arrReturnData = array();

        foreach ($arrData as $rowKey => $rowData)
        {
            $arrReturnData[] = $rowData['values'][$strKey];
        }

        return $arrReturnData;
    }

    /**
     * Static helper method to get all the data from a certain key for all the rows that match a certain other row key
     * @param string
     * @param string
     * @param array
     * @return array
     */
    public static function getFilteredByKey($strSerialized, $strKey, $arrConditions)
    {
        $arrData = deserialize($strSerialized);
        $intCountConditions = count($arrConditions);

        $arrReturnData = array();

        foreach ($arrData as $rowKey => $rowData)
        {
            $intMeetCondition = 0;

            // check data for every filter
            foreach ($arrConditions as $column => $value)
            {
                if ($rowData['values'][$column] == $value)
                {
                    $intMeetCondition++;
                }
            }

            // check if the value meets ALL conditions (AND condition)
            if ($intMeetCondition == $intCountConditions)
            {
                $arrReturnData[] = $rowData['values'][$strKey];
            }
        }

        return $arrReturnData;
    }

}

?>