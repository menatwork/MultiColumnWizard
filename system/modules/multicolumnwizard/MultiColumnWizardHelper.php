<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  Andreas Schempp 2011
 * @copyright  certo web & design GmbH 2011
 * @copyright  MEN AT WORK 2013
 * @package    MultiColumnWizard
 * @license    LGPL
 * @filesource
 */

/**
 * Class MultiColumnWizardHelper
 *
 * @copyright  terminal42 gmbh 2013
 * @package    MultiColumnWizard
 */
class MultiColumnWizardHelper extends System
{
    /**
     * Just here to make the constructor public.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function addScriptsAndStyles(&$objTemplate)
    {
        //do not allow version information to be leaked in the backend login and install tool (#184)
        if ($objTemplate->getName() != 'be_login' && $objTemplate->getName() != 'be_install')
        {
            $GLOBALS['TL_JAVASCRIPT']['mcw'] = $GLOBALS['TL_CONFIG']['debugMode']
                ? 'system/modules/multicolumnwizard/html/js/multicolumnwizard_be_src.js'
                : 'system/modules/multicolumnwizard/html/js/multicolumnwizard_be.js';
            $GLOBALS['TL_CSS']['mcw']        = $GLOBALS['TL_CONFIG']['debugMode']
                ? 'system/modules/multicolumnwizard/html/css/multicolumnwizard_src.css'
                : 'system/modules/multicolumnwizard/html/css/multicolumnwizard.css';
            $objTemplate->ua .= ' version_' . str_replace('.', '-', VERSION) . '-' . str_replace('.', '-', BUILD);
        }
    }

    public function supportModalSelector($strTable)
    {
        if (strpos(Environment::get('script'), 'contao/file.php') !== false)
        {
            list($strField, $strColumn) = explode('__', $this->Input->get('field'));

            if ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['inputType'] == 'multiColumnWizard') {
                $GLOBALS['TL_DCA'][$strTable]['fields'][$strField . '__' . $strColumn] = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['columnFields'][$strColumn];
            }
        }
    }

    /**
     *
     */
    public function changeAjaxPostActions()
    {
        if(version_compare(VERSION,'3.1', '>=')){
            if (Environment::get('isAjaxRequest'))
            {
                switch (Input::post('action'))
                {
                    case 'reloadPagetree':
                    case 'reloadFiletree':
                        //get the fieldnames
                        $strRef = $this->Session->get('filePickerRef');
                        $strRef = substr($strRef, stripos($strRef, 'field=')+6);
                        $arrRef = explode('&', $strRef);
                        $arrRefField = explode('__', $arrRef[0]);
                        $arrField = preg_split('/_row[0-9]*_/i', \Input::post('name'));
                        //change action if modal selector was found
                        if (count($arrRefField) > 1 && $arrRefField === $arrField)
                            Input::setPost('action', Input::post('action').'_mcw');
                        break;
                }
            }
        }

    }

    /**
     *
     * @param type $action
     * @param type $dc
     */
    public function executePostActions($action, \DataContainer $dc)
    {
        if ($action == 'reloadFiletree_mcw' || $action == 'reloadPagetree_mcw' )
        {
            //get the fieldname
            $strRef = $this->Session->get('filePickerRef');
            $strRef = substr($strRef, stripos($strRef, 'field=')+6);
            $arrRef = explode('&', $strRef);
            $strField = $arrRef[0];

            //get value and fieldName
            $strFieldName = \Input::post('name');
            $varValue = \Input::post('value');

            //get the fieldname parts
            $arrfieldParts = preg_split('/_row[0-9]*_/i', $strFieldName);
            preg_match('/_row[0-9]*_/i', $strFieldName, $arrRow);
            $intRow = substr(substr($arrRow[0], 4), 0, -1);

            //build fieldname
            $strFieldName = $arrfieldParts[0] . '[' . $intRow . '][' . $arrfieldParts[1] .']';

            $strKey = ($action == 'reloadPagetree_mcw') ? 'pageTree' : 'fileTree';

            // Convert the selected values
            if ($varValue != '')
            {
                $varValue = trimsplit("\t", $varValue);

                // Automatically add resources to the DBAFS
                if ($strKey == 'fileTree')
                {
                    if(version_compare(VERSION,'3.1', '>=') && version_compare(VERSION,'3.2', '<'))
                    {
                        $fileId = 'id';
                    }
                    if(version_compare(VERSION,'3.2', '>='))
                    {
                        $fileId = 'uuid';
                    }
                    foreach ($varValue as $k=>$v)
                    {
                        $varValue[$k] = \Dbafs::addResource($v)->$fileId;
                    }
                }

                $varValue = serialize($varValue);
            }

            $arrAttribs['id'] = \Input::post('name');
            $arrAttribs['name'] = $strFieldName;
            $arrAttribs['value'] = $varValue;
            $arrAttribs['strTable'] = $dc->table;
            $arrAttribs['strField'] = $strField;

            $objWidget = new $GLOBALS['BE_FFL'][$strKey]($arrAttribs);

            // Re-initialize the activeRecord
            $table = \Input::get('table');
            if ($dc->activeRecord == null && \Database::getInstance()->tableExists($table)) {
                $stmt = \Database::getInstance()
                    ->prepare(sprintf('SELECT * FROM %s WHERE id = ?', $table))
                    ->execute(\Input::get('id'));

                if ($stmt->numRows > 0) {
                    $dc->activeRecord = $stmt;
                    $objWidget->dataContainer = $dc;
                }
            }

            echo $objWidget->generate();
            exit;
        }
    }

    /**
     * Generates a filePicker icon for Contao Version > 3.1
     * @param DataContainer $dc
     *
     * @return string
     */
    public function mcwFilePicker(DataContainer $dc)
    {
        return ' <a href="contao/file.php?do='.Input::get('do').'&amp;table='.$dc->table.'&amp;field='.preg_replace('/_row[0-9]*_/i', '__', $dc->field).'&amp;value='.$dc->value.'" title="'.specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['filepicker'])).'" onclick="Backend.getScrollOffset();Backend.openModalSelector({\'width\':765,\'title\':\''.specialchars($GLOBALS['TL_LANG']['MOD']['files'][0]).'\',\'url\':this.href,\'id\':\''.$dc->field.'\',\'tag\':\'ctrl_'.$dc->field . ((Input::get('act') == 'editAll') ? '_' . $dc->id : '').'\',\'self\':this});return false">' . Image::getHtml('pickfile.gif', $GLOBALS['TL_LANG']['MSC']['filepicker'], 'style="vertical-align:top;cursor:pointer"') . '</a>';
    }
}
