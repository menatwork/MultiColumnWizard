<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2017
 * @author     Sven Baumann <baumann.sv@gmail.com> 2017
 * @package    MultiColumnWizard
 * @license    LGPL
 * @filesource
 */

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\DcGeneralEvents;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use MultiColumnWizard\DcGeneral\UpdateDataDefinition;

if (class_exists(DcGeneralEvents::class)) {
    return array
    (
        PopulateEnvironmentEvent::NAME => array(
            array(
                array(new UpdateDataDefinition(), 'addMcwFields'),
                UpdateDataDefinition::POPULATE_PRIORITY
            )
        ),
        BuildWidgetEvent::NAME => array(
            array(
                array(new UpdateDataDefinition(), 'setModalValue'),
                UpdateDataDefinition::BUILD_WIDGET_PRIORITY
            )
        )
    );
}

return array();
