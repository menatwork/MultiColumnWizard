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
class MultiColumnWizardHelper extends Backend
{

    public function supportModalSelector($strTable)
    {
        if (strpos($this->Environment->script, 'contao/file.php') !== false)
        {
            list($strField, $strColumn) = explode('__', $this->Input->get('field'));

            if ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['inputType'] == 'multiColumnWizard') {
                $GLOBALS['TL_DCA'][$strTable]['fields'][$strField . '__' . $strColumn] = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['columnFields'][$strColumn];
            }
        }
    }
}