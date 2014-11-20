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
	'MultiColumnWizardHelper' => 'system/modules/multicolumnwizard/MultiColumnWizardHelper.php',
	'MultiColumnWizard'       => 'system/modules/multicolumnwizard/MultiColumnWizard.php',
	'MenAtWork\MultiColumnWizard\Event\GetOptionsEvent'       => 'system/modules/multicolumnwizard/src/Event/GetOptionsEvent.php',
));
