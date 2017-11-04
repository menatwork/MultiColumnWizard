<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2017
 * @package    MultiColumnWizard
 * @license    LGPL
 * @filesource
 */

use ContaoCommunityAlliance\DcGeneral\DcGeneralEvents;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use MultiColumnWizard\DcGeneral\UpdateDataDefinition;

if (class_exists(DcGeneralEvents::class)) {
    return array
    (
        DcGeneralEvents::ACTION => array(
            array(
                array(new UpdateDataDefinition(), 'addMcwFieldsByAjax3Action'),
                UpdateDataDefinition::PRIORITY
            )
        ),
        BuildDataDefinitionEvent::NAME => array(
            array(
                array(new UpdateDataDefinition(), 'addMcwFieldsByScopeContaoFile'),
                UpdateDataDefinition::PRIORITY
            )
        )
    );
}

return array();
