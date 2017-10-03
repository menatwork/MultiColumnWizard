<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package Multicolumnwizard
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    // Basic classes.
    'MultiColumnWizardHelper'                           => 'system/modules/multicolumnwizard/MultiColumnWizardHelper.php',
    'MultiColumnWizard'                                 => 'system/modules/multicolumnwizard/MultiColumnWizard.php',
    // Src Folder - MAW (Deprecated)
    'MenAtWork\MultiColumnWizard\Event\GetOptionsEvent' => 'system/modules/multicolumnwizard/src/MenAtWork/Event/GetOptionsEvent.php',
    // Src Folder - MCW
    'MultiColumnWizard\Event\GetOptionsEvent'           => 'system/modules/multicolumnwizard/src/MultiColumnWizard/Event/GetOptionsEvent.php',
    'MultiColumnWizard\DcGeneral\UpdateDataDefinition'  => 'system/modules/multicolumnwizard/src/MultiColumnWizard/DcGeneral/UpdateDataDefinition.php',
));
