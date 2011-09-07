<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
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
 * @copyright  MEN AT WORK 2011, certo web & design GmbH 2011 
 * @package    MultiColumnWizard 
 * @license    LGPL 
 * @filesource
 */


/**
 * Class MultiColumnWizard 
 *
 * @copyright  MEN AT WORK 2011, certo web & design GmbH 2011 
 * @package    Controller
 */
class MultiColumnWizard extends Widget
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
	 * Store in localconfig.php - Javascript-Fallback
	 * @var boolean
	 */
	protected $blnSaveInLocalConfig = false;
	
	/**
	 * Store wherever you want with whatever procedure - Javascript-Fallback
	 * @var array
	 */
	protected $arrStoreCallback = array();
	
	/**
	 * Value
	 * @var mixed
	 */
	protected $varValue = array();
	
	
	/**
	 * Initialize the object
	 * @param array
	 */
	public function __construct($arrAttributes=false)
	{
		parent::__construct($arrAttributes);
		$this->import('Database');
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
				$this->varValue = (array) deserialize($varValue);
				if (!is_array($this->varValue)) $this->varValue = array();				
				break;

			case 'mandatory':
				$this->arrConfiguration['mandatory'] = $varValue ? true : false;
				break;
				
			case 'saveInLocalConfig':
				$this->blnSaveInLocalConfig = $varValue;
				break;
				
			case 'storeCallback':
				if (!is_array($varValue))
				{
					throw new Exception('Parameter "storeCallback" has to be an array: array(\'Class\', \'Method\')!');
				}
				
				$this->arrStoreCallback = $varValue;
				break;
				
			case 'columnsCallback':
				if (!is_array($varValue))
				{
					throw new Exception('Parameter "columns" has to be an array: array(\'Class\', \'Method\')!');
				}
				
				$this->import($varValue[0]);
				$this->columnFields = $this->$varValue[0]->$varValue[1]($this);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	protected function validator($varInput)
	{
		
		$this->blnSubmitInput = true;

		for($i=0; $i<count($varInput); $i++)
		{
                        
			// Walk every column
			foreach($this->columnFields as $strKey => $arrField)
			{   
                           
         		$arrField['name'] = $this->strName.'['.$i.']['.$strKey.']';
				$arrField['id'] = $this->strId.'['.$i.']['.$strKey.']';
				$arrField['value'] = $this->varInput[$i][$strKey];
				// Map checkboxWizard to regular checkbox widget
				if ($arrField['inputType'] == 'checkboxWizard')
				{
					$arrField['inputType'] = 'checkbox';
				}

				$strClass = $GLOBALS['BE_FFL'][$arrField['inputType']];

				
				$arrField['eval']['tableless'] = true;
				$arrField['eval']['required'] = ($this->User->$field == '' && $arrField['eval']['mandatory']) ? true : false;

				$objWidget = new $strClass($this->prepareForWidget($arrField,$arrField['name'],$arrField['value']));

				
				$objWidget->validate();

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
                if (is_array($GLOBALS['TL_JAVASCRIPT']))
		{
			array_insert($GLOBALS['TL_JAVASCRIPT'], 1, 'system/modules/multicolumnwizard/html/js/multicolumnwizard.js');
                        array_insert($GLOBALS['TL_CSS'], 1, 'system/modules/multicolumnwizard/html/css/multicolumnwizard.css');
		}
		else
		{
			$GLOBALS['TL_JAVASCRIPT'] = array('system/modules/multicolumnwizard/html/js/multicolumnwizard.js');
                        $GLOBALS['TL_CSS'] = array('system/modules/multicolumnwizard/html/css/multicolumnwizard.css');
		}
            
		$arrButtons = array('copy', 'up', 'down', 'delete');
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
		}

		// Save the value
		if ($this->Input->get($strCommand) || $this->Input->post('FORM_SUBMIT') == $this->strTable)
		{
			if($this->blnSaveInLocalConfig)
			{
				$this->Config->update(sprintf("\$GLOBALS['TL_CONFIG']['%s']", $this->strField), serialize($this->varValue));
			}
			elseif(is_array($this->arrStoreCallback) && count($this->arrStoreCallback))
			{   
				$strClass = $this->arrStoreCallback[0];
				$strMethod = $this->arrStoreCallback[1];
				$this->import($strClass);
				$this->$strClass->$strMethod($this);
			}
			else
			{
				$this->Database->prepare("UPDATE " . $this->strTable . " SET " . $this->strField . "=? WHERE id=?")
						   	   ->execute(serialize($this->varValue), $this->currentRecord);		
			}

			// Reload the page
			if (is_numeric($this->Input->get('cid')) && $this->Input->get('id') == $this->currentRecord)
			{
				$this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($strCommand, '/') . '=[^&]*/i', '', $this->Environment->request)));
			}
		}
		
		// Add label and return wizard
		$return .= '<table cellspacing="0" '.(($this->style)? ('style="'.$this->style.'"') : ('')).'rel="maxCount['.(($this->maxCount)? $this->maxCount : '0').']" cellpadding="0" id="ctrl_'.$this->strId.'" class="tl_modulewizard multicolumnwizard" summary="MultiColumnWizard">
		<thead>
		<tr>';
		
		$arrHeaderItems = array();
		
		
		foreach($this->columnFields as $strKey=>$arrField)
		{
			$label = $strKey;

			if ($arrField['eval']['columnPos'])
			{
				$arrHeaderItems[$arrField['eval']['columnPos']]  = '';
			}
			else
			{
				if ($arrField['label'][0])
					$arrHeaderItems[]  = $arrField['label'][0];
				else
					$arrHeaderItems[]  = $label;
			}
			
		}

		foreach ($arrHeaderItems as $itemKey=>$itemValue)
		{   
			$arrHeaderItems[$itemKey] = '<td>'.$itemValue.'</td>';
		}
   
		$return .= implode("",$arrHeaderItems);

    	$return .= '<td></td>
	  	</tr>
	  	</thead>
	  	<tbody>';
    	
    	$intNumberOfRows = max(count($this->varValue), 1);

		// Add input fields
		for($i=0; $i<$intNumberOfRows; $i++)
		{
                    $arrItem = array();
			$return .= '<tr>';
                        $columnCount = 1;
			// Walk every column
			foreach($this->columnFields as $strKey => $arrField)
			{   
                            if (!$arrField['eval']['columnPos'])
                                unset($arrField['label']);

				$arrField['name'] = $this->strName.'['.$i.']['.$strKey.']';
				$arrField['id'] = $this->strId.'['.$i.']['.$strKey.']';
				$arrField['value'] = $this->varValue[$i][$strKey];
				// Map checkboxWizard to regular checkbox widget
				if ($arrField['inputType'] == 'checkboxWizard')
				{
					$arrField['inputType'] = 'checkbox';
				}

				$strClass = $GLOBALS['BE_FFL'][$arrField['inputType']];

				
				$arrField['eval']['tableless'] = true;
				$arrField['eval']['required'] = ($this->User->$field == '' && $arrField['eval']['mandatory']) ? true : false;
 
				$objWidget = new $strClass($this->prepareForWidget($arrField,$arrField['name'],$arrField['value']));

				$objWidget->storeValues = true;
				$objWidget->rowClass = 'row_'.$row . (($row == 0) ? ' row_first' : '') . ((($row % 2) == 0) ? ' even' : ' odd');

				if ($this->Input->post('FORM_SUBMIT'))
				{
					$objWidget->validate();
					$varValue = $objWidget->value;

					$rgxp = $arrField['eval']['rgxp'];

					// Convert date formats into timestamps (check the eval setting first -> #3063)
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
								$varValue = $this->$callback[0]->$callback[1]($varValue, $this->User);
							}
							catch (Exception $e)
							{
								$objWidget->class = 'error';
								$objWidget->addError($e->getMessage());
							}
						}
					}
                                        
					$objWidget->validate();
                                        
					// Do not submit if there are errors
					if ($objWidget->hasErrors())
					{
						$doNotSubmit = true;
					}

					
				}
                                
                                // Add custom wizard
                                $wizard = '';
                                if (is_array($arrField['wizard']))
                                {
                                        foreach ($arrField['wizard'] as $callback)
                                        {
                                            $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
                                            require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));
                                            $dc = new $dataContainer($this->strTable);
                                            $dc->field = $arrField['name'];
                                            $dc->inputName = $arrField['name'];											
                                            $this->import($callback[0]);
                                            $wizard .= $this->$callback[0]->$callback[1]($dc);
                                            unset($dc);
                                                
                                        }
                                }
                               
                                $objWidget->__set('wizard', $wizard);
                                
                                //Build array of items
                                if ($objWidget->columnPos)
                                {
                                    $arrItem[$objWidget->columnPos]['entry'] .= $objWidget->parse();
                                    $arrItem[$objWidget->columnPos]['valign'] = $arrField['eval']['valign'];
                                }
                                else
                                {
                                    $arrItem[] = array(
                                        'entry' => $objWidget->parse(),
                                        'valign' => $arrField['eval']['valign']);
                                }
                                
                            $columnCount++;
			}
                        
                        // new array for items so we get rid of the ['entry'] and ['valign']
                        $arrReturnItems = array();
                        foreach ($arrItem as $itemKey=>$itemValue)
			{       
                            $arrReturnItems[$itemKey] = '<td'.(($itemValue['valign'] != '') ? ' valign="'.$itemValue['valign'].'" ' : '').'>'.$itemValue['entry'].'</td>';
			}

			$return .= implode("",$arrReturnItems);
                        
                        $return .= '<td class="last"'.(($this->buttonPos != '') ? ' valign="'.$this->buttonPos.'" ' : '').'>';

                        if ($this->maxCount < $intNumberOfRows && $this->maxCount >0)
                        {
                            $keys = array_keys($arrButtons, 'copy');
                            unset($arrButtons[$keys[0]]);
                        }
                        
                        // Add buttons
                        foreach ($arrButtons as $button)
                        {
                                $return .= '<a href="'.$this->addToUrl('&'.$strCommand.'='.$button.'&cid='.$i.'&id='.$this->currentRecord).'" class="widgetImage" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['wz_'.$button]).'" onclick="MultiSelect.moduleWizard(this, \''.$button.'\',  \'ctrl_'.$this->strId.'\'); return false;">'.$this->generateImage($button.'.gif', $GLOBALS['TL_LANG'][$this->strTable]['wz_'.$button], 'class="tl_listwizard_img"').'</a> ';
                        }
			
			$return .= '</td></tr>';
		}

		return $return . '</tbody></table>';
	}
	
	
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
		
		foreach($arrData as $rowKey => $rowData)
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

		foreach($arrData as $rowKey => $rowData)
		{
			$intMeetCondition = 0;

			// check data for every filter
			foreach($arrConditions as $column => $value)
			{
				if($rowData['values'][$column] == $value)
				{
					$intMeetCondition++;
				}
			}

			// check if the value meets ALL conditions (AND condition)
			if($intMeetCondition == $intCountConditions)
			{
				$arrReturnData[] = $rowData['values'][$strKey];
			}
		}

		return $arrReturnData;                
	}
        
        /**
	 * Convert a back end DCA so it can be used with the widget class
	 * @param array
	 * @param string
	 * @param mixed
	 * @param string
	 * @param string
	 * @return array
	 */
	protected function prepareForWidget($arrData, $strName, $varValue=null, $strField='', $strTable='')
	{
            
                $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'];
                require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));
                $dc = new $dataContainer($this->strTable);
            
		$arrNew = $arrData['eval'];
		$arrNew['id'] = $strName;
		$arrNew['name'] = $strName;
		$arrNew['strField'] = $strField;
		$arrNew['strTable'] = $strTable;
		$arrNew['label'] = (($label = is_array($arrData['label']) ? $arrData['label'][0] : $arrData['label']) != false) ? $label : $strField;
		$arrNew['description'] = $arrData['label'][1];
		$arrNew['type'] = $arrData['inputType'];

		// Internet Explorer does not support onchange for checkboxes and radio buttons
		if ($arrData['inputType'] == 'checkbox' || $arrData['inputType'] == 'checkboxWizard' || $arrData['inputType'] == 'radio' || $arrData['inputType'] == 'radioTable')
		{
			$arrNew['onclick'] = $arrData['eval']['submitOnChange'] ? "Backend.autoSubmit('".$strTable."');" : '';
		}
		else
		{
			$arrNew['onchange'] = $arrData['eval']['submitOnChange'] ? "Backend.autoSubmit('".$strTable."');" : '';
		}

		$arrNew['allowHtml'] = ($arrData['eval']['allowHtml'] || strlen($arrData['eval']['rte']) || $arrData['eval']['preserveTags']) ? true : false;

		// Decode entities if HTML is allowed
		if ($arrNew['allowHtml'] || $arrData['inputType'] == 'fileTree')
		{
			$arrNew['decodeEntities'] = true;
		}

		// Add Ajax event
		if ($arrData['inputType'] == 'checkbox' && is_array($GLOBALS['TL_DCA'][$strTable]['subpalettes']) && in_array($strField, array_keys($GLOBALS['TL_DCA'][$strTable]['subpalettes'])) && $arrData['eval']['submitOnChange'])
		{
			$arrNew['onclick'] = "AjaxRequest.toggleSubpalette(this, 'sub_".$strName."', '".$strField."');";
		}

		// Options callback
		if (is_array($arrData['options_callback']))
		{   
                    
			if (!is_object($arrData['options_callback'][0]))
			{       
				$this->import($arrData['options_callback'][0]);
			}
  
			$arrData['options'] = $this->$arrData['options_callback'][0]->$arrData['options_callback'][1]($dc);
   
		}
                
                // load callback
		if (is_array($arrData['load_callback']))
		{   

                        foreach ($arrData['load_callback'] as $callback)
                            {
                                    if (is_array($callback))
                                    {
                                            $this->import($callback[0]);
                                            $varValue = $this->$callback[0]->$callback[1]($varValue, $dc);
                                    }
                            }
   
		}

		// Foreign key
		if (strlen($arrData['foreignKey']))
		{
			$arrKey = explode('.', $arrData['foreignKey']);
			$objOptions = $this->Database->execute("SELECT id, " . $arrKey[1] . " FROM " . $arrKey[0] . " WHERE tstamp>0 ORDER BY " . $arrKey[1]);

			if ($objOptions->numRows)
			{
				$arrData['options'] = array();

				while($objOptions->next())
				{
					$arrData['options'][$objOptions->id] = $objOptions->$arrKey[1];
				}
			}
		}

		// Add default option to single checkbox
		if ($arrData['inputType'] == 'checkbox' && !isset($arrData['options']) && !isset($arrData['options_callback']) && !isset($arrData['foreignKey']))
		{
			if (TL_MODE == 'FE' && isset($arrNew['description']))
			{
				$arrNew['options'][] = array('value'=>1, 'label'=>$arrNew['description']);
			}
			else
			{
				$arrNew['options'][] = array('value'=>1, 'label'=>$arrNew['label']);
			}
		}

		// Add options
		if (is_array($arrData['options']))
		{
			$blnIsAssociative = array_is_assoc($arrData['options']);
			$blnUseReference = isset($arrData['reference']);

			if ($arrData['eval']['includeBlankOption'])
			{
				$strLabel = strlen($arrData['eval']['blankOptionLabel']) ? $arrData['eval']['blankOptionLabel'] : '-';
				$arrNew['options'][] = array('value'=>'', 'label'=>$strLabel);
			}

			foreach ($arrData['options'] as $k=>$v)
			{
				if (!is_array($v))
				{
					$arrNew['options'][] = array('value'=>($blnIsAssociative ? $k : $v), 'label'=>($blnUseReference ? ((($ref = (is_array($arrData['reference'][$v]) ? $arrData['reference'][$v][0] : $arrData['reference'][$v])) != false) ? $ref : $v) : $v));
					continue;
				}

				$key = $blnUseReference ? ((($ref = (is_array($arrData['reference'][$k]) ? $arrData['reference'][$k][0] : $arrData['reference'][$k])) != false) ? $ref : $k) : $k;
				$blnIsAssoc = array_is_assoc($v);

				foreach ($v as $kk=>$vv)
				{
					$arrNew['options'][$key][] = array('value'=>($blnIsAssoc ? $kk : $vv), 'label'=>($blnUseReference ? ((($ref = (is_array($arrData['reference'][$vv]) ? $arrData['reference'][$vv][0] : $arrData['reference'][$vv])) != false) ? $ref : $vv) : $vv));
				}
			}
		}

		$arrNew['value'] = deserialize($varValue);

		// Convert timestamps
		if ($varValue != '' && in_array($arrData['eval']['rgxp'], array('date', 'time', 'datim')))
		{
			$objDate = new Date($varValue);
			$arrNew['value'] = $objDate->{$arrData['eval']['rgxp']};
		}
		
		return $arrNew;
	}
}

?>