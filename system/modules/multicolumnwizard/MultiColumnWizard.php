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
 * @copyright  Andreas Schempp 2011
 * @copyright  certo web & design GmbH 2011
 * @copyright  MEN AT WORK 2011
 * @package    MultiColumnWizard 
 * @license    LGPL 
 * @filesource
 * @info       tab is set to 4 whitespaces
 */

/**
 * Class MultiColumnWizard 
 *
 * @copyright  Andreas Schempp 2011
 * @copyright  certo web & design GmbH 2011
 * @copyright  MEN AT WORK 2011
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
     * Widget errors to store
     * @var array
     */
    protected $arrWidgetErrors = array();

    /**
     * Callback data
     * @var array
     */
    protected $arrCallback = false;

    /**
     * Min count
     * @var int
     */
    protected $minCount = 0;

    /**
     * Max count
     * @var int
     */
    protected $maxCount = 0;

    /**
     * Tableless
     * @var boolean
     */
    protected $blnTableless = false;

    /**
     * Row specific data
     * @var array
     */
    protected $arrRowSpecificData = array();

    /**
     * Buttons
     * @var array
     */
    protected $arrButtons = array('copy'   => 'copy.gif', 'up'     => 'up.gif', 'down'   => 'down.gif', 'delete' => 'delete.gif');

    /**
     * Initialize the object
     * @param array
     */
    public function __construct($arrAttributes = false)
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

                /**
                 * reformat array if we have only one field 
                 * from array[] = value
                 * to array[]['fieldname'] = value 
                 */
                if ($this->flatArray)
                {
                    $arrNew = array();

                    foreach ($this->varValue as $val)
                    {
                        $arrNew[] = array(key($this->columnFields) => $val);
                    }

                    $this->varValue = $arrNew;
                }
                break;

            case 'mandatory':
                $this->arrConfiguration['mandatory'] = $varValue ? true : false;
                break;

            case 'columnsCallback':
                if (!is_array($varValue))
                {
                    throw new Exception('Parameter "columns" has to be an array: array(\'Class\', \'Method\')!');
                }

                $this->arrCallback = $varValue;
                break;

            case 'buttons':
                if (is_array($varValue))
                {
                    $this->arrButtons = array_merge($this->arrButtons, array_intersect_key($varValue, $this->arrButtons));
                }
                break;

            case 'hideButtons':
                if ($varValue === true)
                {
                    $this->arrButtons = array();
                }

            case 'disableSorting':
                if ($varValue == true)
                {
                    unset($this->arrButtons['up']);
                    unset($this->arrButtons['down']);
                }
                break;

            case 'minCount':
                $this->minCount = $varValue;
                break;

            case 'maxCount':
                $this->maxCount = $varValue;
                break;

            case 'generateTableless':
                $this->blnTableless = $varValue;
                break;

            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }

    public function __get($strKey)
    {
        switch ($strKey)
        {
            case 'value':
                /**
                 * reformat array if we have only one field
                 * from array[]['fieldname'] = value
                 * to array[] = value
                 * so we have the same behavoir like multiple-checkbox fields
                 */
                if ($this->flatArray)
                {
                    $arrNew = array();

                    foreach ($this->varValue as $val)
                    {
                        $arrNew[] = $val[key($this->columnFields)];
                    }

                    return $arrNew;
                }
                else
                {
                    return parent::__get($strKey);
                }
                break;

            default:
                return parent::__get($strKey);
                break;
        }
    }

    protected function validator($varInput)
    {
    	$blnHasError = false;

        for ($i = 0; $i < count($varInput); $i++)
        {
            $this->activeRow = $i;

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
                    $objDate  = new Date($varValue, $GLOBALS['TL_CONFIG'][$rgxp . 'Format']);
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
                    // store the errors
                    $this->arrWidgetErrors[$strKey][$i] = $objWidget->getErrors();

                    $blnHasError = true;
                }
            }
        }

        if ($blnHasError)
        {
        	$this->blnSubmitInput = false;
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
        // load the callback data if there's any (do not do this in __set() already because then we don't have access to currentRecord)
        if (is_array($this->arrCallback))
        {
            $this->import($this->arrCallback[0]);
            $this->columnFields = $this->{$this->arrCallback[0]}->{$this->arrCallback[1]}($this);
        }

        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/multicolumnwizard/html/js/multicolumnwizard_' . strtolower(TL_MODE) . '.js';
        $GLOBALS['TL_CSS'][]        = 'system/modules/multicolumnwizard/html/css/multicolumnwizard.css';

        $this->strCommand = 'cmd_' . $this->strField;

        // Change the order
        if ($this->Input->get($this->strCommand) && is_numeric($this->Input->get('cid')) && $this->Input->get('id') == $this->currentRecord)
        {

            switch ($this->Input->get($this->strCommand))
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

            // Save in File
            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'] == 'File')
            {
                $this->Config->update(sprintf("\$GLOBALS['TL_CONFIG']['%s']", $this->strField), serialize($this->varValue));

                // Reload the page
                $this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($this->strCommand, '/') . '=[^&]*/i', '', $this->Environment->request)));
            }
            // Save in table
            else if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'] == 'Table')
            {
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['save_callback']))
                {
                    $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
                    require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));

                    $dc            = new $dataContainer($this->strTable);
                    $dc->field     = $objWidget->id;
                    $dc->inputName = $objWidget->id;

                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['save_callback'] AS $callback)
                    {
                        $this->import($callback[0]);
                        $this->$callback[0]->$callback[1](serialize($this->varValue), $dc);
                    }
                }
                else
                {
                    $this->Database->prepare("UPDATE " . $this->strTable . " SET " . $this->strField . "=? WHERE id=?")
                            ->execute(serialize($this->varValue), $this->currentRecord);
                }

                // Reload the page
                $this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($this->strCommand, '/') . '=[^&]*/i', '', $this->Environment->request)));
            }
            // Unknow
            else
            {
               // What to do here?
            }
        }

        $arrUnique = array();
        $arrDatepicker = array();
        $arrTinyMCE = array();
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
            
            // Store tiny mce fields
            if ($arrField['eval']['rte'] && strncmp($arrField['eval']['rte'], 'tiny', 4) === 0)
            {
                $GLOBALS['TL_RTE']['tinyMCE'][$this->strField . '_' . $strKey] = array(
                    'id'   => $this->strField . '_' . $strKey,
                    'file' => 'tinyMCE',
                    'type' => null
                );

                $arrTinyMCE[] = $strKey;
            }

            if ($arrField['inputType'] == 'hidden')
            {
                continue;
            }
        }

        $intNumberOfRows = max(count($this->varValue), 1);

        // always show the minimum number of rows if set
        if ($this->minCount && ($intNumberOfRows < $this->minCount))
        {
            $intNumberOfRows = $this->minCount;
        }

        $arrItems = array();
        $arrHiddenHeader = array();

        // Add input fields
        for ($i = 0; $i < $intNumberOfRows; $i++)
        {
            $this->activeRow = $i;
            $strHidden       = '';
            $blnHiddenBody   = false;

            // Walk every column
            foreach ($this->columnFields as $strKey => $arrField)
            {
                $strWidget     = '';
                $blnHiddenBody = false;

                // load row specific data (useful for example for default values in different rows)
                if (isset($this->arrRowSpecificData[$i][$strKey]))
                {
                    $arrField = array_merge($arrField, $this->arrRowSpecificData[$i][$strKey]);
                }

                $objWidget = $this->initializeWidget($arrField, $i, $strKey, $this->varValue[$i][$strKey]);

                // load errors if there are any
                if (!empty($this->arrWidgetErrors[$strKey][$i]))
                {
                    foreach ($this->arrWidgetErrors[$strKey][$i] as $strErrorMsg)
                    {
                        $objWidget->addError($strErrorMsg);
                    }
                }

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
                elseif ($arrField['eval']['hideBody'] == true || $arrField['eval']['hideHead'] == true)
                {
                    if ($arrField['eval']['hideHead'] == true)
                    {
                        $arrHiddenHeader[$strKey] = true;
                    }

                    if ($arrField['eval']['hideBody'] == true)
                    {
                        $blnHiddenBody = true;
                    }
                    
                    $strWidget = $objWidget->parse();
                }
                else
                {
                    $datepicker = '';
                    $tinyMce    = '';

                    // Datepicker
                    if ($arrField['eval']['datepicker'])
                    {
                        $rgxp   = $arrField['eval']['rgxp'];
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

                        $datepicker = ' <img src="system/modules/multicolumnwizard/html/img/icon.gif" width="20" height="20" alt="" id="toggle_' . $objWidget->id . '" style="vertical-align:-6px;">
                          <script>

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

                          </script>';

                        $datepicker = $this->getMcWDatePickerString($objWidget->id, $strKey, $rgxp);

                        /* $datepicker = '<script>
                          window.addEvent(\'domready\', function() {
                          ' . sprintf($this->getDatePickerString(), 'ctrl_' . $objWidget->strId) . '
                          });
                          </script>'; */
                    }

                    // Tiny MCE
                    if ($arrField['eval']['rte'] && strncmp($arrField['eval']['rte'], 'tiny', 4) === 0)
                    {
                        $tinyMce = $this->getMcWTinyMCEString($objWidget->id);
                        $arrField['eval']['tl_class'] .= ' tinymce';
                    }

                    // Add custom wizard
                    if (is_array($arrField['wizard']))
                    {
                        $wizard = '';

                        $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
                        require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));

                        $dc            = new $dataContainer($this->strTable);
                        $dc->field     = $objWidget->id;
                        $dc->inputName = $objWidget->id;

                        foreach ($arrField['wizard'] as $callback)
                        {
                            $this->import($callback[0]);
                            $wizard .= $this->$callback[0]->$callback[1]($dc, $objWidget);
                        }

                        $objWidget->wizard = $wizard;
                    }
                    
                    $strWidget = $objWidget->parse() . $datepicker . $tinyMce;
                }
                                
                // Build array of items
                if ($arrField['eval']['columnPos'] != '')
                {
                    $arrItems[$i][$objWidget->columnPos]['entry'] .= $strWidget;
                    $arrItems[$i][$objWidget->columnPos]['valign']   = $arrField['eval']['valign'];
                    $arrItems[$i][$objWidget->columnPos]['tl_class'] = $arrField['eval']['tl_class'];
                    $arrItems[$i][$objWidget->columnPos]['hide']     = $blnHiddenBody;
                }
                else
                {
                    $arrItems[$i][$strKey] = array
                        (
                        'entry'    => $strWidget,
                        'valign'   => $arrField['eval']['valign'],
                        'tl_class' => $arrField['eval']['tl_class'],
                        'hide'     => $blnHiddenBody
                    );
                }
            }
        }

        $strOutput = '';

        if ($this->blnTableless)
        {
            $strOutput = $this->generateDiv($arrUnique, $arrDatepicker, $strHidden, $arrItems, $arrHiddenHeader);
        }
        else
        {
            if ($this->columnTemplate != '')
            {
                $strOutput = $this->generateTemplateOutput($arrUnique, $arrDatepicker, $strHidden, $arrItems, $arrHiddenHeader);
            }
            else
            {
                $strOutput = $this->generateTable($arrUnique, $arrDatepicker, $strHidden, $arrItems, $arrHiddenHeader);
            }
        }

        return $strOutput;
    }

    protected function getMcWDatePickerString($strId, $strKey, $rgxp)
    {
        if (version_compare(VERSION, '2.11', '<'))
        {
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

            return ' <img src="system/modules/multicolumnwizard/html/img/icon.gif" width="20" height="20" alt="" id="toggle_' . $strId . '" style="vertical-align:-6px;">
                          <script>
                        window.addEvent("domready", function() {
                          window.datepicker_' . $this->strName . '_' . $strKey . ' = new DatePicker(\'#ctrl_' . $strId . '\', {
                          allowEmpty:true,
                          toggleElements:\'#toggle_' . $strId . '\',
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
                          </script>';
        }
        else
        {
            $format = Date::formatToJs($GLOBALS['TL_CONFIG'][$rgxp . 'Format']);
            switch ($rgxp)
            {
                case 'datim':
                    $time = ",\n      timePicker:true";
                    break;

                case 'time':
                    $time = ",\n      pickOnly:\"time\"";
                    break;

                default:
                    $time = '';
                    break;
            }

            return ' <img src="system/modules/multicolumnwizard/html/img/icon.gif" width="20" height="20" alt="" id="toggle_' . $strId . '" style="vertical-align:-6px">
                        <script>
                        window.addEvent("domready", function() {
                            new Picker.Date($$("#ctrl_' . $strId . '"), {
                            draggable:false,
                            toggle:$$("#toggle_' . $strId . '"),
                            format:"' . $format . '",
                            positionOffset:{x:-197,y:-182}' . $time . ',
                            pickerClass:"datepicker_dashboard",
                            useFadeInOut:!Browser.ie,
                            startDay:' . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ',
                            titleFormat:"' . $GLOBALS['TL_LANG']['MSC']['titleFormat'] . '"
                            });
                        });
                        </script>';
        }
    }

    protected function getMcWTinyMCEString($strId)
    {
        return "<script>
            tinyMCE.execCommand('mceAddControl', false, 'ctrl_" . $strId . "');
            $('ctrl_" . $strId . "').erase('required');
                </script>";
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
        $xlabel          = '';
        $strContaoPrefix = 'contao/';

        // YACE support for leo unglaub :)
        if (defined('YACE'))
            $strContaoPrefix = '';

        // Toggle line wrap (textarea)
        if ($arrField['inputType'] == 'textarea' && $arrField['eval']['rte'] == '')
        {
            $xlabel .= ' ' . $this->generateImage('wrap.gif', $GLOBALS['TL_LANG']['MSC']['wordWrap'], 'title="' . specialchars($GLOBALS['TL_LANG']['MSC']['wordWrap']) . '" class="toggleWrap" onclick="Backend.toggleWrap(\'ctrl_' . $this->strId . '_row' . $intRow . '_' . $strKey . '\');"');
        }

        // Add the help wizard
        if ($arrField['eval']['helpwizard'])
        {
            $xlabel .= ' <a href="' . $strContaoPrefix . 'help.php?table=' . $this->strTable . '&amp;field=' . $this->strName . '_' . $strKey . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['helpWizard']) . '" rel="lightbox[help 610 80%]">' . $this->generateImage('about.gif', $GLOBALS['TL_LANG']['MSC']['helpWizard'], 'style="vertical-align:text-bottom;"') . '</a>';
        }

        // Add the popup file manager
        if ($arrField['inputType'] == 'fileTree')
        {
            $path = '';

            if (isset($arrField['eval']['path']))
            {
                $path = '?node=' . $arrField['eval']['path'];
            }

            $xlabel .= ' <a href="' . $strContaoPrefix . 'files.php' . $path . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['fileManager']) . '" rel="lightbox[files 765 80%]">' . $this->generateImage('filemanager.gif', $GLOBALS['TL_LANG']['MSC']['fileManager'], 'style="vertical-align:text-bottom;"') . '</a>';
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

        // add class to enable easy updating of "name" attributes etc.
        $arrField['eval']['tl_class'] = trim($arrField['eval']['tl_class'] . ' mcwUpdateFields');

        // load callback
        if (is_array($arrField['load_callback']))
        {
            foreach ($arrField['load_callback'] as $callback)
            {
                $this->import($callback[0]);
                $varValue = $this->$callback[0]->$callback[1]($varValue, $this);
            }
        }

        $arrField['name']              = $this->strName . '[' . $intRow . '][' . $strKey . ']';
        $arrField['id']                = $this->strId . '_row' . $intRow . '_' . $strKey;
        $arrField['value']             = ($varValue !== '') ? $varValue : $arrField['default'];
        $arrField['eval']['tableless'] = true;

        $objWidget = new $strClass($this->prepareForWidget($arrField, $arrField['name'], $arrField['value'], null, $this->strTable));

        $objWidget->strId         = $arrField['id'];
        $objWidget->storeValues   = true;
        $objWidget->xlabel        = $xlabel;
        $objWidget->currentRecord = $this->currentRecord;

        return $objWidget;
    }

    /**
     * Add specific field data to a certain field in a certain row
     * @param integer row index
     * @param string field name
     * @param array field data
     */
    public function addDataToFieldAtIndex($intIndex, $strField, $arrData)
    {
        $this->arrRowSpecificData[$intIndex][$strField] = $arrData;
    }

    /**
     * Generates a table formatted MCW
     * @param array
     * @param array
     * @param string
     * @param array
     * @return string
     */
    protected function generateTable($arrUnique, $arrDatepicker, $strHidden, $arrItems, $arrHiddenHeader = array())
    {

        // generate header fields
        foreach ($this->columnFields as $strKey => $arrField)
        {

            if ($arrField['eval']['columnPos'])
            {
                $arrHeaderItems[$arrField['eval']['columnPos']] = (key_exists($strKey, $arrHiddenHeader)) ? '<td class="invisible">' : '<td>' . '</td>';
            }
            else
            {                
                $arrHeaderItems[] = (key_exists($strKey, $arrHiddenHeader)) ? '<td class="invisible">' : '<td>';
                $arrHeaderItems[] .= (is_array($arrField['label'])) ? $arrField['label'][0] : ($arrField['label'] != null ? $arrField['label'] : $strKey);
                $arrHeaderItems[] .= ((is_array($arrField['label']) && $arrField['label'][1] != '') ? '<span title="' . $arrField['label'][1] . '"><sup>(?)</sup></span>' : '');
                $arrHeaderItems[] .= '</td>';
            }
        }


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

        foreach ($arrItems as $k => $arrValue)
        {
            $return .= '<tr>';
            foreach ($arrValue as $itemKey => $itemValue)
            {
                if ($itemValue['hide'] == true)
                {
                    $itemValue['tl_class'] .= ' invisible';
                }
                
                $return .= '<td' . ($itemValue['valign'] != '' ? ' valign="' . $itemValue['valign'] . '"' : '') . ($itemValue['tl_class'] != '' ? ' class="' . $itemValue['tl_class'] . '"' : '') . '>' . $itemValue['entry'] . '</td>';
            }

            // insert buttons at the very end
            $return .= '<td class="operations col_last"' . (($this->buttonPos != '') ? ' valign="' . $this->buttonPos . '" ' : '') . '>' . $strHidden;
            $return .= $this->generateButtonString($k);
            $return .= '</td>';
            $return .= '</tr>';
        }

        $return .= '</tbody></table>';

        $return .= '<script>
		window["MCW_" + ' . json_encode($this->strId) . '] = new MultiColumnWizard({
			table: "ctrl_" + ' . json_encode($this->strId) . ',
			maxCount: ' . intval($this->maxCount) . ',
			minCount: ' . intval($this->minCount) . ',
			uniqueFields: [] // TODO: implement
		});
		</script>';

        return $return;
    }

    protected function generateTemplateOutput($arrUnique, $arrDatepicker, $strHidden, $arrItems)
    {
        $objTemplate        = new BackendTemplate($this->columnTemplate);
        $objTemplate->items = $arrItems;

        $arrButtons = array();
        foreach ($arrItems as $k => $arrValue)
        {
            $arrButtons[$k]       = $this->generateButtonString($k);
        }
        $objTemplate->buttons = $arrButtons;

        return $objTemplate->parse();
    }

    /**
     * Generates a div formatted MCW
     * @param array
     * @param array
     * @param string
     * @param array
     * @return string
     */
    protected function generateDiv($arrUnique, $arrDatepicker, $strHidden, $arrItems, $arrHiddenHeader = array())
    {
        // generate header fields
        foreach ($this->columnFields as $strKey => $arrField)
        {
            if (key_exists($strKey, $arrHiddenHeader))
            {
                $strKey = $strKey . ' invisible';
            }
            
            $arrHeaderItems[] = sprintf('<div class="%s">%s</div>', $strKey, ($arrField['label'][0] ? $arrField['label'][0] : $strKey));
        }


        $return = '<div' . (($this->style) ? (' style="' . $this->style . '"') : '') . ' rel="maxCount[' . ($this->maxCount ? $this->maxCount : '0') . '] minCount[' . ($this->minCount ? $this->minCount : '0') . '] unique[' . implode(',', $arrUnique) . '] datepicker[' . implode(',', $arrDatepicker) . ']" id="ctrl_' . $this->strId . '" class="tl_modulewizard multicolumnwizard">';
        $return .= '<div class="header_fields">' . implode('', $arrHeaderItems) . '</div>';



        // new array for items so we get rid of the ['entry'] and ['valign']
        $arrReturnItems = array();

        foreach ($arrItems as $itemKey => $itemValue)
        {
            if ($itemValue['hide'])
            {
                $itemValue['tl_class'] .= ' invisible';
            }
            
            $arrReturnItems[$itemKey] = '<div' . ($itemValue['tl_class'] != '' ? ' class="' . $itemValue['tl_class'] . '"' : '') . '>' . $itemValue['entry'] . '</div>';
        }

        $return .= implode('', $arrReturnItems);



        $return .= '<div class="col_last buttons">' . $this->generateButtonString($strKey) . '</div>';

        $return .= $strHidden;

        return $return . '</div>';
    }

    /**
     * Generate button string
     * @return string
     */
    protected function generateButtonString($level = 0)
    {
        $return = '';

        // Add buttons
        foreach ($this->arrButtons as $button => $image)
        {

            if ($image === false)
            {
                continue;
            }

            $return .= '<a rel="' . $button . '" href="' . $this->addToUrl('&' . $this->strCommand . '=' . $button . '&cid=' . $level . '&id=' . $this->currentRecord) . '" class="widgetImage" title="' . $GLOBALS['TL_LANG']['MSC']['tw_r' . specialchars($button) . ''] . '">' . $this->generateImage($image, $GLOBALS['TL_LANG']['MSC']['tw_r' . specialchars($button) . ''], 'class="tl_listwizard_img"') . '</a> ';
        }

        return $return;
    }

}

