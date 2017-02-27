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
 * Class MultiColumnWizard
 *
 * @copyright  Andreas Schempp 2011
 * @copyright  certo web & design GmbH 2011
 * @copyright  MEN AT WORK 2013
 * @package    MultiColumnWizard
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
                    $this->arrButtons = array_merge($this->arrButtons, $varValue);
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

            if (!$this->columnFields)
            {
                continue;
            }

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
                if (!$objWidget->hasErrors() && ($rgxp == 'date' || $rgxp == 'time' || $rgxp == 'datim') && $varValue != '')
                {
                    $objDate  = new Date($varValue,$this->getNumericDateFormat($rgxp));
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
                            $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $this);
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

                    $blnHasError = \Input::post('SUBMIT_TYPE') != 'auto';
                }
            }
        }

        if ($this->minCount > 0 && count($varInput) < $this->minCount)
        {
            $this->blnSubmitInput = false;
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mcwMinCount'], $this->minCount));
        }

        if ($this->maxCount > 0 && count($varInput) > $this->maxCount)
        {
            $this->blnSubmitInput = false;
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mcwMaxCount'], $this->maxCount));
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

        // use BE script in FE for now
        $GLOBALS['TL_JAVASCRIPT']['mcw'] = $GLOBALS['TL_CONFIG']['debugMode']
            ? 'system/modules/multicolumnwizard/html/js/multicolumnwizard_be_src.js'
            : 'system/modules/multicolumnwizard/html/js/multicolumnwizard_be.js';
        $GLOBALS['TL_CSS']['mcw']        = $GLOBALS['TL_CONFIG']['debugMode']
            ? 'system/modules/multicolumnwizard/html/css/multicolumnwizard_src.css'
            : 'system/modules/multicolumnwizard/html/css/multicolumnwizard.css';

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
                $this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($this->strCommand, '/') . '=[^&]*/i', '', Environment::get('request'))));
            }
            // Save in table
            else if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'] == 'Table')
            {
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['save_callback']))
                {
                    $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
                    // If less than 3.X, we must load the class by hand.
                    if (version_compare(VERSION, '3.0', '<'))
                    {
                        require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));
                    }

                    $dc            = new $dataContainer($this->strTable);
                    $dc->field     = $objWidget->id;
                    $dc->inputName = $objWidget->id;

                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['save_callback'] AS $callback)
                    {
                        $this->import($callback[0]);
                        $this->{$callback[0]}->{$callback[1]}(serialize($this->varValue), $dc);
                    }
                }
                else
                {
                    $this->Database->prepare("UPDATE " . $this->strTable . " SET " . $this->strField . "=? WHERE id=?")
                            ->execute(serialize($this->varValue), $this->currentRecord);
                }

                // Reload the page
                $this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($this->strCommand, '/') . '=[^&]*/i', '', Environment::get('request'))));
            }
            // Unknow
            else
            {
               // What to do here?
            }
        }

        $arrUnique = array();
        $arrDatepicker = array();
        $arrColorpicker = array();
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

            // Store color picker fields
            if ($arrField['eval']['colorpicker'])
            {
				$arrColorpicker[] = $strKey;
            }

            // Store tiny mce fields
            if ($arrField['eval']['rte'] && strncmp($arrField['eval']['rte'], 'tiny', 4) === 0)
            {
                foreach ($this->varValue as $row => $value) {
                    $tinyId = 'ctrl_' . $this->strField . '_row' . $row . '_' . $strKey;

                    $GLOBALS['TL_RTE']['tinyMCE'][$tinyId] = array(
                        'id'   => $tinyId,
                        'file' => 'tinyMCE',
                        'type' => null
                    );
                }

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

            // Walk every column
            foreach ($this->columnFields as $strKey => $arrField)
            {
                $strWidget     = '';
                $blnHiddenBody = false;

                if ($arrField['eval']['hideHead'] == true)
                {
                    $arrHiddenHeader[$strKey] = true;
                }

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
                    if ($arrField['eval']['hideBody'] == true)
                    {
                        $blnHiddenBody = true;
                    }

                    $strWidget = $objWidget->parse();
                }
                else
                {
                    $datepicker = '';
                    $colorpicker = '';
                    $tinyMce    = '';

                    // Datepicker
                    if ($arrField['eval']['datepicker'])
                    {
                        $rgxp   = $arrField['eval']['rgxp'];
                        $format = $this->getNumericDateFormat($rgxp);

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

                        $datepicker = ' <img src="system/modules/multicolumnwizard/html/img/datepicker.gif" width="20" height="20" alt="" id="toggle_' . $objWidget->id . '" style="vertical-align:-6px;">
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
					
					// Color picker
					if ($arrField['eval']['colorpicker'])
					{
						// Support single fields as well (see #5240)
						//$strKey = $arrData['eval']['multiple'] ? $this->strField . '_0' : $this->strField;
			
						$colorpicker = ' ' . \Image::getHtml('pickcolor.gif', $GLOBALS['TL_LANG']['MSC']['colorpicker'], 'style="vertical-align:top;cursor:pointer" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['colorpicker']).'" id="moo_' . $objWidget->id . '"') . '
			  <script>
				window.addEvent("domready", function() {
				  new MooRainbow("moo_' . $objWidget->id . '", {
					id: "ctrl_' . $objWidget->id . '",
					startColor: ((cl = $("ctrl_' . $objWidget->id . '").value.hexToRgb(true)) ? cl : [255, 0, 0]),
					imgPath: "assets/mootools/colorpicker/' . $GLOBALS['TL_ASSETS']['COLORPICKER'] . '/images/",
					onComplete: function(color) {
					  $("ctrl_' . $objWidget->id . '").value = color.hex.replace("#", "");
					}
				  });
				});
			  </script>';
					}
					

                    // Tiny MCE
                    if ($arrField['eval']['rte'] && strncmp($arrField['eval']['rte'], 'tiny', 4) === 0)
                    {
                        $tinyMce = $this->getMcWTinyMCEString($objWidget->id, $arrField);
                        $arrField['eval']['tl_class'] .= ' tinymce';
                    }

                    // Add custom wizard
                    if (is_array($arrField['wizard']))
                    {
                        $wizard = '';

                        $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
                        // If less than 3.X, we must load the class by hand.
                        if (version_compare(VERSION, '3.0', '<'))
                        {
                            require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));
                        }

                        $dc            = new $dataContainer($this->strTable);
                        $dc->field     = $objWidget->id;
                        $dc->inputName = $objWidget->id;
                        $dc->value     = $objWidget->value;

                        foreach ($arrField['wizard'] as $callback)
                        {
                            $this->import($callback[0]);
                            $wizard .= $this->{$callback[0]}->{$callback[1]}($dc, $objWidget);
                        }

                        $objWidget->wizard = $wizard;
                    }

                    $strWidget = $objWidget->parse() . $datepicker . $colorpicker . $tinyMce;
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
            $strOutput = $this->generateDiv($arrUnique, $arrDatepicker, $arrColorpicker, $strHidden, $arrItems, $arrHiddenHeader);
        }
        else
        {
            if ($this->columnTemplate != '')
            {
                $strOutput = $this->generateTemplateOutput($arrUnique, $arrDatepicker, $arrColorpicker, $strHidden, $arrItems, $arrHiddenHeader);
            }
            else
            {
                $strOutput = $this->generateTable($arrUnique, $arrDatepicker, $arrColorpicker, $strHidden, $arrItems, $arrHiddenHeader);
            }
        }

        return $strOutput;
    }

    protected function getMcWDatePickerString($strId, $strKey, $rgxp)
    {
        if (version_compare(VERSION, '2.11', '<'))
        {
            $format = $this->getNumericDateFormat($rgxp);
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

            return ' <img src="system/modules/multicolumnwizard/html/img/datepicker.gif" width="20" height="20" alt="" id="toggle_' . $strId . '" style="vertical-align:-6px;">
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

        elseif (version_compare(VERSION,'3.3','<')) {

            $format = Date::formatToJs($this->getNumericDateFormat($rgxp));
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

            return ' <img src="system/modules/multicolumnwizard/html/img/datepicker.gif" width="20" height="20" alt="" id="toggle_' . $strId . '" style="vertical-align:-6px;cursor:pointer;">
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

        else
        {
            $format = Date::formatToJs($this->getNumericDateFormat($rgxp));
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

            return ' <img src="system/modules/multicolumnwizard/html/img/datepicker.gif" width="20" height="20" alt="" id="toggle_' . $strId . '" style="vertical-align:-6px;cursor:pointer;">
                        <script>
                        window.addEvent("domready", function() {
                            new Picker.Date($("ctrl_' . $strId . '"), {
                            draggable:false,
                            toggle:$("toggle_' . $strId . '"),
                            format:"' . $format . '",
                            positionOffset:{x:-211,y:-209}' . $time . ',
                            pickerClass:"datepicker_bootstrap",
                            useFadeInOut:!Browser.ie,
                            startDay:' . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ',
                            titleFormat:"' . $GLOBALS['TL_LANG']['MSC']['titleFormat'] . '"
                            });
                        });
                        </script>';
        }
    }

    protected function getMcWTinyMCEString($strId, $arrField)
    {
        if (version_compare(VERSION, '3.3', '<'))
        {
            return "<script>
            tinyMCE.execCommand('mceAddControl', false, 'ctrl_" . $strId . "');
            $('ctrl_" . $strId . "').erase('required');
                </script>";
        }

        list ($file, $type) = explode('|', $arrField['eval']['rte'], 2);

        if (!file_exists(TL_ROOT . '/system/config/' . $file . '.php'))
        {
            throw new \Exception(sprintf('Cannot find editor configuration file "%s.php"', $file));
        }

        // Backwards compatibility
        $language = substr($GLOBALS['TL_LANGUAGE'], 0, 2);

        if (!file_exists(TL_ROOT . '/assets/tinymce/langs/' . $language . '.js'))
        {
            $language = 'en';
        }

        $selector = 'ctrl_' . $strId;

        ob_start();
        include TL_ROOT . '/system/config/' . $file . '.php';
        $editor = ob_get_contents();
        ob_end_clean();

        return $editor;
    }

    /**
     * Initialize widget
     *
     * Based on DataContainer::row() from Contao 2.10.1
     *
     * @param   array
     * @param   int
     * @param   string
     * @param   mixed
     * @return  Widget|null
     */
    protected function initializeWidget(&$arrField, $intRow, $strKey, $varValue)
    {
        $xlabel          = '';
        $strContaoPrefix = 'contao/';

        // YACE support for leo unglaub :)
        if (defined('YACE'))
            $strContaoPrefix = '';

        //pass activeRecord to widget
        $arrField['activeRecord'] = $this->activeRecord;

        // Toggle line wrap (textarea)
        if ($arrField['inputType'] == 'textarea' && $arrField['eval']['rte'] == '')
        {
            $xlabel .= ' ' . $this->generateImage('wrap.gif', $GLOBALS['TL_LANG']['MSC']['wordWrap'], 'title="' . specialchars($GLOBALS['TL_LANG']['MSC']['wordWrap']) . '" class="toggleWrap" onclick="Backend.toggleWrap(\'ctrl_' . $this->strId . '_row' . $intRow . '_' . $strKey . '\');"');
        }

        // Add the help wizard
        if ($arrField['eval']['helpwizard'])
        {
            if(version_compare(VERSION,'3.1', '<')){
                $xlabel .= ' <a href="contao/help.php?table=' . $this->strTable . '&amp;field=' . $this->strField . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['helpWizard']) . '" data-lightbox="help 610 80%">' . $this->generateImage('about.gif', $GLOBALS['TL_LANG']['MSC']['helpWizard'], 'style="vertical-align:text-bottom"') . '</a>';
            } else {
                $xlabel .= ' <a href="contao/help.php?table=' . $this->strTable . '&amp;field=' . $this->strField . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['helpWizard']) . '" onclick="Backend.openModalIframe({\'width\':735,\'height\':405,\'title\':\'' . specialchars(str_replace("'", "\\'", $arrField['label'][0])) . '\',\'url\':this.href});return false">' . \Image::getHtml('about.gif', $GLOBALS['TL_LANG']['MSC']['helpWizard'], 'style="vertical-align:text-bottom"') . '</a>';
            }
        }

        // Add the popup file manager
        if ($arrField['inputType'] == 'fileTree' || $arrField['inputType'] == 'pageTree')
        {
            $path = '';

            if (isset($arrField['eval']['path']))
            {
                $path = '?node=' . $arrField['eval']['path'];
            }

            $xlabel .= ' <a href="' . $strContaoPrefix . 'files.php' . $path . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['fileManager']) . '" rel="lightbox[files 765 80%]">' . $this->generateImage('filemanager.gif', $GLOBALS['TL_LANG']['MSC']['fileManager'], 'style="vertical-align:text-bottom;"') . '</a>';

            $arrField['strField'] = $this->strField . '__' . $strKey;
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
            if (!is_object($this->{$arrField['input_field_callback'][0]}))
            {
                $this->import($arrField['input_field_callback'][0]);
            }

            return $this->{$arrField['input_field_callback'][0]}->{$arrField['input_field_callback'][1]}($this, $xlabel);
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

        // if we have a row class, add that one aswell.
        if (isset($arrField['eval']['rowClasses'][$intRow]))
        {
            $arrField['eval']['tl_class'] = trim($arrField['eval']['tl_class'] . ' ' . $arrField['eval']['rowClasses'][$intRow]);
        }

        // load callback
        if (is_array($arrField['load_callback']))
        {
            foreach ($arrField['load_callback'] as $callback)
            {
                $this->import($callback[0]);
                $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $this);
            }
        }

        // Convert date formats into timestamps (check the eval setting first -> #3063)
        $rgxp = $arrField['eval']['rgxp'];
        $dateFormatErrorMsg="";
        if (($rgxp == 'date' || $rgxp == 'time' || $rgxp == 'datim') && $varValue != '')
        {
            try{
                $objDate  = new Date($varValue, $this->getNumericDateFormat($rgxp));
            }catch(\Exception $e){
                $dateFormatErrorMsg=$e->getMessage();
            }

            $varValue = $objDate->tstamp;
        }

        $arrField['activeRow']         = $intRow;
        $arrField['name']              = $this->strName . '[' . $intRow . '][' . $strKey . ']';
        $arrField['id']                = $this->strId . '_row' . $intRow . '_' . $strKey;
        $arrField['value']             = ($varValue !== '') ? $varValue : $arrField['default'];
        $arrField['eval']['tableless'] = true;

        $arrData = $this->handleDcGeneral($arrField, $strKey);
        if(version_compare(VERSION,'3.1', '<')){
            $objWidget = new $strClass($this->prepareForWidget($arrData, $arrField['name'], $arrField['value'], $arrField['strField'], $this->strTable));
        }
        else{
            $objWidget = new $strClass(\MultiColumnWizard::getAttributesFromDca($arrData, $arrField['name'], $arrField['value'], $arrField['strField'], $this->strTable, $this));
        }

        $objWidget->strId         = $arrField['id'];
        $objWidget->storeValues   = true;
        $objWidget->xlabel        = $xlabel;
        $objWidget->currentRecord = $this->currentRecord;
        if(!empty($dateFormatErrorMsg)){
            $objWidget->addError($e->getMessage());
        }

        return $objWidget;
    }

    /**
     * Check if DcGeneral version 2+ is calling us and if so, handle GetPropertyOptionsEvent accordingly.
     *
     * @param array  $arrData The field configuration array
     * @param string $strName The field name in the form
     *
     * @return array The processed field configuration array.
     */
    public function handleDcGeneral($arrData, $strName)
    {
        // DcGeneral 2.0 compatibility check.
        if (is_subclass_of($this->objDca, 'ContaoCommunityAlliance\DcGeneral\EnvironmentAwareInterface'))
        {
            // If options-callback registered, call that one first as otherwise \Widget::getAttributesFromDca will kill
            // our options.
            if (is_array($arrData['options_callback']))
            {
                $arrCallback = $arrData['options_callback'];
                $arrData['options'] = static::importStatic($arrCallback[0])->{$arrCallback[1]}($this);
                unset($arrData['options_callback']);
            }
            elseif (is_callable($arrData['options_callback']))
            {
                $arrData['options'] = $arrData['options_callback']($this);
                unset($arrData['options_callback']);
            }

            /* @var \ContaoCommunityAlliance\DcGeneral\EnvironmentInterface $environment */
            $environment = $this->objDca->getEnvironment();
            // FIXME: begin of legacy code to be removed.
            if (method_exists($environment, 'getEventPropagator'))
            {
                $event   = new \ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent($environment, $this->objDca->getModel());
                $event->setPropertyName($strName);
                $event->setOptions($arrData['options']);
                $environment->getEventPropagator()->propagate(
                    $event::NAME,
                    $event,
                    array(
                        $environment->getDataDefinition()->getName(),
                        $this->strName,
                        $strName
                    )
                );

                if ($event->getOptions() !== $arrData['options'])
                {
                    $arrData['options'] = $event->getOptions();
                }
            }
            // FIXME: end of legacy code to be removed.

            $event = new \MenAtWork\MultiColumnWizard\Event\GetOptionsEvent(
                $this->strName,
                $strName,
                $environment,
                $this->objDca->getModel(),
                $this,
                $arrData['options']
            );
            $environment->getEventDispatcher()->dispatch($event::NAME, $event);

            if ($event->getOptions() !== $arrData['options'])
            {
                $arrData['options'] = $event->getOptions();
            }
        }

        return $arrData;
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
    protected function generateTable($arrUnique, $arrDatepicker, $arrColorpicker, $strHidden, $arrItems, $arrHiddenHeader = array())
    {

        // generate header fields
        foreach ($this->columnFields as $strKey => $arrField)
        {

            if ($arrField['eval']['columnPos'])
            {
                $arrHeaderItems[$arrField['eval']['columnPos']] = '<td></td>';
            }
            else
            {
                $strHeaderItem = '<td>';

                $strHeaderItem .= (key_exists($strKey, $arrHiddenHeader)) ? '<div class="invisible">' : '';
                $strHeaderItem .= (is_array($arrField['label'])) ? $arrField['label'][0] : ($arrField['label'] != null ? $arrField['label'] : $strKey);
                $strHeaderItem .= ((is_array($arrField['label']) && $arrField['label'][1] != '') ? '<span title="' . $arrField['label'][1] . '"><sup>(?)</sup></span>' : '');
                $strHeaderItem .= (key_exists($strKey, $arrHiddenHeader)) ? '</div>' : '';

                $arrHeaderItems[] = $strHeaderItem . '</td>';
            }
        }


        $return = '
<table cellspacing="0" ' . (($this->style) ? ('style="' . $this->style . '"') : ('')) . 'rel="maxCount[' . ($this->maxCount ? $this->maxCount : '0') . '] minCount[' . ($this->minCount ? $this->minCount : '0') . '] unique[' . implode(',', $arrUnique) . '] datepicker[' . implode(',', $arrDatepicker) . '] colorpicker[' . implode(',', $arrColorpicker) . ']" cellpadding="0" id="ctrl_' . $this->strId . '" class="tl_modulewizard multicolumnwizard" summary="MultiColumnWizard">';

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
        window.addEvent("load", function() {
            window["MCW_" + ' . json_encode($this->strId) . '] = new MultiColumnWizard({
                table: "ctrl_" + ' . json_encode($this->strId) . ',
                maxCount: ' . intval($this->maxCount) . ',
                minCount: ' . intval($this->minCount) . ',
                uniqueFields: [] // TODO: implement
            });
        });
        </script>';

        return $return;
    }

    protected function generateTemplateOutput($arrUnique, $arrDatepicker, $arrColorpicker, $strHidden, $arrItems)
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
    protected function generateDiv($arrUnique, $arrDatepicker, $arrColorpicker, $strHidden, $arrItems, $arrHiddenHeader = array())
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


        $return = '<div' . (($this->style) ? (' style="' . $this->style . '"') : '') . ' rel="maxCount[' . ($this->maxCount ? $this->maxCount : '0') . '] minCount[' . ($this->minCount ? $this->minCount : '0') . '] unique[' . implode(',', $arrUnique) . '] datepicker[' . implode(',', $arrDatepicker) . '] colorpicker[' . implode(',', $arrColorpicker) . ']" id="ctrl_' . $this->strId . '" class="tl_modulewizard multicolumnwizard">';
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

    /**
     * Get Time/Date-format from global config (BE) or Page settings (FE)
     * @param $rgxp
     *
     * @return mixed
     */
    private function getNumericDateFormat($rgxp){
        return call_user_func(array("\Contao\Date","getNumeric".ucfirst($rgxp)."Format" ));
    }


}
