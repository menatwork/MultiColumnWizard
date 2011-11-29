<?php
if (!defined('TL_ROOT')) die('You cannot access this file directly!');

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

/**
 * Class MultiColumnWizard 
 *
 * @copyright  Andreas Schempp 2011, certo web & design GmbH 2011, MEN AT WORK 2011
 * @package    Controller
 */
class MultiColumnWizard extends Widget implements uploadable
{

    /**
     * Submit user input
     * @var boolean
     */
    protected $blnSubmitInput = true;

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Value
     * @var mixed
     */
    protected $varValue = array();

    /**
     * Buttons
     * @var array
     */
    protected $arrButtons = array('copy' => 'copy.gif', 'up' => 'up.gif', 'down' => 'down.gif', 'delete' => 'delete.gif');

    /**
     * Initialize the object
     * @param array
     */
    public function __construct($arrAttributes=false)
    {
        parent::__construct($arrAttributes);
        $this->import('Database');

        if (TL_MODE == 'FE')
        {
            $this->strTemplate = 'form_widget';
            $this->loadDataContainer($arrAttributes['strTable']);
        }
    }

    /**
     * Add specific attributes
     * @param string
     * @param mixed
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey)
        {
            case 'value':
                $this->varValue = deserialize($varValue, true);
                break;

            case 'mandatory':
                $this->arrConfiguration['mandatory'] = $varValue ? true : false;
                break;

            case 'columnsCallback':
                if (!is_array($varValue))
                {
                    throw new Exception('Parameter "columns" has to be an array: array(\'Class\', \'Method\')!');
                }

                $this->import($varValue[0]);
                $this->columnFields = $this->$varValue[0]->$varValue[1]($this);
                break;

            case 'buttons':
                if (is_array($varValue))
                {
                    $this->arrButtons = array_merge($this->arrButtons, array_intersect_key($varValue, $this->arrButtons));
                }
                break;

            case 'disableSorting':
                if ($varValue == true)
                {
                    unset($this->arrButtons['up']);
                    unset($this->arrButtons['down']);
                }
                break;

            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }

    protected function validator($varInput)
    {
        for ($i = 0; $i < count($varInput); $i++)
        {
            // Walk every column
            foreach ($this->columnFields as $strKey => $arrField)
            {
                $objWidget = $this->initializeWidget($arrField, $i, $strKey, $varInput[$i][$strKey]);

                // can be null on error, or a string on input_field_callback
                if (!is_object($objWidget))
                {
                    continue;
                }

                // hack for checkboxes
                if ($arrField['inputType'] == 'checkbox' && isset($varInput[$i][$strKey]))
                {
                    $_POST[$objWidget->name] = $varInput[$i][$strKey];
                }

                $objWidget->validate();

                $varValue = $objWidget->value;

                // Convert date formats into timestamps (check the eval setting first -> #3063)
                $rgxp = $arrField['eval']['rgxp'];
                if (($rgxp == 'date' || $rgxp == 'time' || $rgxp == 'datim') && $varValue != '')
                {
                    $objDate = new Date($varValue, $GLOBALS['TL_CONFIG'][$rgxp . 'Format']);
                    $varValue = $objDate->tstamp;
                }

                // Save callback
                if (is_array($arrField['save_callback']))
                {
                    foreach ($arrField['save_callback'] as $callback)
                    {
                        $this->import($callback[0]);

                        try
                        {
                            $varValue = $this->$callback[0]->$callback[1]($varValue, $this);
                        }
                        catch (Exception $e)
                        {
                            $objWidget->class = 'error';
                            $objWidget->addError($e->getMessage());
                        }
                    }
                }

                $varInput[$i][$strKey] = $varValue;

                // Do not submit if there are errors
                if ($objWidget->hasErrors())
                {
                    $this->blnSubmitInput = false;
                }
            }
        }

        if (!$this->blnSubmitInput)
        {
            $this->addError($GLOBALS['TL_LANG']['ERR']['general']);
        }

        return $varInput;
    }

    /**
     * Generate the widget and return it as string
     * @return string
     */
    public function generate()
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/multicolumnwizard/html/js/multicolumnwizard.js';
        $GLOBALS['TL_CSS'][] = 'system/modules/multicolumnwizard/html/css/multicolumnwizard.css';

        $strCommand = 'cmd_' . $this->strField;

        // Change the order
        if ($this->Input->get($strCommand) && is_numeric($this->Input->get('cid')) && $this->Input->get('id') == $this->currentRecord)
        {
            switch ($this->Input->get($strCommand))
            {
                case 'copy':
                    $this->varValue = array_duplicate($this->varValue, $this->Input->get('cid'));
                    break;

                case 'up':
                    $this->varValue = array_move_up($this->varValue, $this->Input->get('cid'));
                    break;

                case 'down':
                    $this->varValue = array_move_down($this->varValue, $this->Input->get('cid'));
                    break;

                case 'delete':
                    $this->varValue = array_delete($this->varValue, $this->Input->get('cid'));
                    break;
            }

            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'] == 'File')
            {
                $this->Config->update(sprintf("\$GLOBALS['TL_CONFIG']['%s']", $this->strField), serialize($this->varValue));
            }
            else
            {
                $this->Database->prepare("UPDATE " . $this->strTable . " SET " . $this->strField . "=? WHERE id=?")
                        ->execute(serialize($this->varValue), $this->currentRecord);
            }

            // Reload the page
            $this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($strCommand, '/') . '=[^&]*/i', '', $this->Environment->request)));
        }

        $arrUnique = array();
        $arrDatepicker = array();
        $arrHeaderItems = array();

        foreach ($this->columnFields as $strKey => $arrField)
        {
            // Store unique fields
            if ($arrField['eval']['unique'])
            {
                $arrUnique[] = $strKey;
            }

            // Store date picker fields
            if ($arrField['eval']['datepicker'])
            {
                $arrDatepicker[] = $strKey;
            }

            if ($arrField['inputType'] == 'hidden')
            {
                continue;
            }
            elseif ($arrField['eval']['columnPos'])
            {
                $arrHeaderItems[$arrField['eval']['columnPos']] = '<td></td>';
            }
            else
            {
                $arrHeaderItems[] = '<td>' . ($arrField['label'][0] ? $arrField['label'][0] : $strKey) . '</td>';
            }
        }

        // Add label and return wizard
        $return = '
<table cellspacing="0" ' . (($this->style) ? ('style="' . $this->style . '"') : ('')) . 'rel="maxCount[' . ($this->maxCount ? $this->maxCount : '0') . '] minCount[' . ($this->minCount ? $this->minCount : '0') . '] unique[' . implode(',', $arrUnique) . '] datepicker[' . implode(',', $arrDatepicker) . ']" cellpadding="0" id="ctrl_' . $this->strId . '" class="tl_modulewizard multicolumnwizard" summary="MultiColumnWizard">';

		if ($this->columnTemplate == '')
        {
			$return .= '
  <thead>
    <tr>
      ' . implode("\n      ", $arrHeaderItems) . '
      <td></td>
    </tr>
  </thead>';
  }
  
  $return .='
  <tbody>';

        $intNumberOfRows = max(count($this->varValue), 1);

        // Add input fields
        for ($i = 0; $i < $intNumberOfRows; $i++)
        {
            $arrItem = array();
            $strHidden = '';
            $return .= '<tr>';

            // Walk every column
            foreach ($this->columnFields as $strKey => $arrField)
            {
                $strWidget = '';
                $objWidget = $this->initializeWidget($arrField, $i, $strKey, $this->varValue[$i][$strKey]);

                if ($objWidget === null)
                {
                    continue;
                }
                elseif (is_string($objWidget))
                {
                    $strWidget = $objWidget;
                }
                elseif ($arrField['inputType'] == 'hidden')
                {
                    $strHidden .= $objWidget->generate();
                    continue;
                }
                else
                {
                    $datepicker = '';

                    // Datepicker
                    if ($arrField['eval']['datepicker'])
                    {
                        $rgxp = $arrField['eval']['rgxp'];
                        $format = $GLOBALS['TL_CONFIG'][$rgxp . 'Format'];

                        switch ($rgxp)
                        {
                            case 'datim':
                                $time = ",\n      timePicker:true";
                                break;

                            case 'time':
                                $time = ",\n      timePickerOnly:true";
                                break;

                            default:
                                $time = '';
                                break;
                        }

                        /* $datepicker = ' <img src="plugins/datepicker/icon.gif" width="20" height="20" alt="" id="toggle_' . $objWidget->id . '" style="vertical-align:-6px;">
<script>
window.addEvent(\'domready\', function() {
window.datepicker_' . $this->strName . '_' . $strKey . ' = new DatePicker(\'#ctrl_' . $objWidget->id . '\', {
  allowEmpty:true,
  toggleElements:\'#toggle_' . $objWidget->id . '\',
  pickerClass:\'datepicker_dashboard\',
  format:\'' . $format . '\',
  inputOutputFormat:\'' . $format . '\',
  positionOffset:{x:130,y:-185}' . $time . ',
  startDay:' . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ',
  days:[\'' . implode("','", $GLOBALS['TL_LANG']['DAYS']) . '\'],
  dayShort:' . $GLOBALS['TL_LANG']['MSC']['dayShortLength'] . ',
  months:[\'' . implode("','", $GLOBALS['TL_LANG']['MONTHS']) . '\'],
  monthShort:' . $GLOBALS['TL_LANG']['MSC']['monthShortLength'] . '
});
});
</script>'; */
                        $datepicker = '<script>
window.addEvent(\'domready\', function() {
' . sprintf($this->getDatePickerString(), 'ctrl_' . $objWidget->strId) . '
});
</script>';
                    }

                    // Add custom wizard
                    if (is_array($arrField['wizard']))
                    {
                        $wizard = '';

                        $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
                        require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));

                        $dc = new $dataContainer($this->strTable);
                        $dc->field = $objWidget->id;
                        $dc->inputName = $objWidget->id;

                        foreach ($arrField['wizard'] as $callback)
                        {
                            $this->import($callback[0]);
                            $wizard .= $this->$callback[0]->$callback[1]($dc, $objWidget);
                        }

                        $objWidget->wizard = $wizard;
                    }

                    $strWidget = $objWidget->parse() . $datepicker;
                }

                // Build array of items
                if ($arrField['eval']['columnPos'] != '')
                {
                    $arrItem[$objWidget->columnPos]['entry'] .= $strWidget;
                    $arrItem[$objWidget->columnPos]['valign'] = $arrField['eval']['valign'];
                    $arrItem[$objWidget->columnPos]['tl_class'] = $arrField['eval']['tl_class'];
                }
                else
                {
                    $arrItem[$strKey] = array
                        (
                        'entry' => $strWidget,
                        'valign' => $arrField['eval']['valign'],
                        'tl_class' => $arrField['eval']['tl_class'],
                    );
                }
            }

            // new array for items so we get rid of the ['entry'] and ['valign']
            $arrReturnItems = array();
			
			if ($this->columnTemplate != '')
            {
				$objTemplate = new BackendTemplate($this->columnTemplate);
				$objTemplate->items = $arrItem;
				
				$return .= '<td>'.$objTemplate->parse().'</td>';
			}
			else
			{
				foreach ($arrItem as $itemKey => $itemValue)
				{
					$arrReturnItems[$itemKey] = '<td' . ($itemValue['valign'] != '' ? ' valign="' . $itemValue['valign'] . '"' : '') . ($itemValue['tl_class'] != '' ? ' class="' . $itemValue['tl_class'] . '"' : '') . '>' . $itemValue['entry'] . '</td>';
				}
				
				$return .= implode('', $arrReturnItems);
			}

            

            $return .= '<td class="col_last"' . (($this->buttonPos != '') ? ' valign="' . $this->buttonPos . '" ' : '') . '>' . $strHidden;

            // Add buttons
            foreach ($this->arrButtons as $button => $image)
            {
                $return .= '<a ';
                $style = '';
                if ($button == "copy" && $this->maxCount <= $intNumberOfRows && $this->maxCount > 0)
                {
                    $return .= 'style="display:none" ';
                }
                if ($button == "delete" && $this->minCount >= $intNumberOfRows && $this->minCount > 0)
                {
                    $return .= 'style="display:none" ';
                }

                $return .= 'href="' . $this->addToUrl('&' . $strCommand . '=' . $button . '&cid=' . $i . '&id=' . $this->currentRecord) . '" class="widgetImage" title="' . specialchars($GLOBALS['TL_LANG'][$this->strTable]['wz_' . $button]) . '" onclick="MultiColumnWizard.execute(this, \'' . $button . '\',  \'ctrl_' . $this->strId . '\'); return false;">' . $this->generateImage($image, $GLOBALS['TL_LANG'][$this->strTable]['wz_' . $button], 'class="tl_listwizard_img"') . '</a> ';
            }

            $return .= '</td></tr>';
        }

        return $return . '</tbody></table>';
    }

    /**
     * Initialize widget
     *
     * Based on DataContainer::row() from Contao 2.10.1
     *
     * @param	array
     * @param	int
     * @param	string
     * @param	mixed
     * @return	Widget|null
     */
    protected function initializeWidget(&$arrField, $intRow, $strKey, $varValue)
    {
        $xlabel = '';

        // Toggle line wrap (textarea)
        if ($arrField['inputType'] == 'textarea' && $arrField['eval']['rte'] == '')
        {
            $xlabel .= ' ' . $this->generateImage('wrap.gif', $GLOBALS['TL_LANG']['MSC']['wordWrap'], 'title="' . specialchars($GLOBALS['TL_LANG']['MSC']['wordWrap']) . '" class="toggleWrap" onclick="Backend.toggleWrap(\'ctrl_' . $this->strId . '_row' . $intRow . '_' . $strKey . '\');"');
        }

        // Add the help wizard
        if ($arrField['eval']['helpwizard'])
        {
            $xlabel .= ' <a href="contao/help.php?table=' . $this->strTable . '&amp;field=' . $this->strName . '_' . $strKey . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['helpWizard']) . '" rel="lightbox[help 610 80%]">' . $this->generateImage('about.gif', $GLOBALS['TL_LANG']['MSC']['helpWizard'], 'style="vertical-align:text-bottom;"') . '</a>';
        }

        // Add the popup file manager
        if ($arrField['inputType'] == 'fileTree')
        {
            $path = '';

            if (isset($arrField['eval']['path']))
            {
                $path = '?node=' . $arrField['eval']['path'];
            }

            $xlabel .= ' <a href="contao/files.php' . $path . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['fileManager']) . '" rel="lightbox[files 765 80%]">' . $this->generateImage('filemanager.gif', $GLOBALS['TL_LANG']['MSC']['fileManager'], 'style="vertical-align:text-bottom;"') . '</a>';
        }

        // Add the table import wizard
        elseif ($arrField['inputType'] == 'tableWizard')
        {
            $xlabel .= ' <a href="' . $this->addToUrl('key=table') . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['tw_import'][1]) . '" onclick="Backend.getScrollOffset();">' . $this->generateImage('tablewizard.gif', $GLOBALS['TL_LANG']['MSC']['tw_import'][0], 'style="vertical-align:text-bottom;"') . '</a>';
            $xlabel .= ' ' . $this->generateImage('demagnify.gif', '', 'title="' . specialchars($GLOBALS['TL_LANG']['MSC']['tw_shrink']) . '" style="vertical-align:text-bottom; cursor:pointer;" onclick="Backend.tableWizardResize(0.9);"') . $this->generateImage('magnify.gif', '', 'title="' . specialchars($GLOBALS['TL_LANG']['MSC']['tw_expand']) . '" style="vertical-align:text-bottom; cursor:pointer;" onclick="Backend.tableWizardResize(1.1);"');
        }

        // Add the list import wizard
        elseif ($arrField['inputType'] == 'listWizard')
        {
            $xlabel .= ' <a href="' . $this->addToUrl('key=list') . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['lw_import'][1]) . '" onclick="Backend.getScrollOffset();">' . $this->generateImage('tablewizard.gif', $GLOBALS['TL_LANG']['MSC']['tw_import'][0], 'style="vertical-align:text-bottom;"') . '</a>';
        }

        // Input field callback
        if (is_array($arrField['input_field_callback']))
        {
            if (!is_object($this->$arrField['input_field_callback'][0]))
            {
                $this->import($arrField['input_field_callback'][0]);
            }

            return $this->$arrField['input_field_callback'][0]->$arrField['input_field_callback'][1]($this, $xlabel);
        }

        $strClass = $GLOBALS[(TL_MODE == 'BE' ? 'BE_FFL' : 'TL_FFL')][$arrField['inputType']];

        if ($strClass == '' || !$this->classFileExists($strClass))
        {
            return null;
        }

        $arrField['eval']['required'] = false;

        // Use strlen() here (see #3277)
        if ($arrField['eval']['mandatory'])
        {
            if (is_array($this->varValue[$intRow][$strKey]))
            {
                if (empty($this->varValue[$intRow][$strKey]))
                {
                    $arrField['eval']['required'] = true;
                }
            }
            else
            {
                if (!strlen($this->varValue[$intRow][$strKey]))
                {
                    $arrField['eval']['required'] = true;
                }
            }
        }

        // Hide label except if multiple widgets are in one column
        if ($arrField['eval']['columnPos'] == '')
        {
            $arrField['eval']['tl_class'] = trim($arrField['eval']['tl_class'] . ' hidelabel');
        }

        // load callback
        if (is_array($arrField['load_callback']))
        {
            foreach ($arrField['load_callback'] as $callback)
            {
                $this->import($callback[0]);
                $varValue = $this->$callback[0]->$callback[1]($varValue, $this);
            }
        }

        $arrField['name'] = $this->strName . '[' . $intRow . '][' . $strKey . ']';
        $arrField['id'] = $this->strId . '_row' . $intRow . '_' . $strKey;
        $arrField['value'] = $varValue ? $varValue : $arrField['default'];
        $arrField['eval']['tableless'] = true;

        $objWidget = new $strClass($this->prepareForWidget($arrField, $arrField['name'], $arrField['value'], null, $this->strTable));

        $objWidget->strId = $arrField['id'];
        $objWidget->storeValues = true;
        $objWidget->xlabel = $xlabel;
        $objWidget->currentRecord = $this->currentRecord;

        return $objWidget;
    }

}

?>