<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2017
 * @package    MultiColumnWizard
 * @license    LGPL
 * @filesource
 */

namespace MultiColumnWizard\DcGeneral;

use Contao\Environment;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\DefaultProperty;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PropertiesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;

/**
 * Class UpdateDataDefinition
 *
 * @package MultiColumnWizard\DcGeneral
 */
class UpdateDataDefinition
{
    const PRIORITY = -500000;

    /**
     * Add all fields from the MCW to the DCA. This is needed for some fields, because other components need this
     * to create the widget/view etc.
     *
     * @param BuildDataDefinitionEvent $event
     *
     * @return void
     */
    public function addMcwFieldsByScopeContaoFile (BuildDataDefinitionEvent $event) {
        if (false === in_array(Environment::get('scriptName'), array(TL_PATH . '/contao/file.php', TL_PATH . '/contao/page.php'))) {
            return;
        }

        $this->addMcwColumnProperties($event->getContainer()->getPropertiesDefinition());
    }

    /**
     * Add all fields from the MCW to the DCA. This is needed for some fields, because other components need this
     * to create the widget/view etc.
     *
     * @param ActionEvent $event
     *
     * @return void
     */
    public function addMcwFieldsByAjax3Action(ActionEvent $event)
    {
        if ('ajax3' !== $event->getAction()->getName()) {
            return;
        }

        $this->addMcwColumnProperties($event->getEnvironment()->getDataDefinition()->getPropertiesDefinition());
    }

    /**
     * Add the MCW column properties.
     *
     * @param PropertiesDefinitionInterface $properties
     *
     * @return void
     */
    private function addMcwColumnProperties(PropertiesDefinitionInterface $properties)
    {
        /** @var DefaultProperty $property */
        foreach ($properties as $property) {
            // Only run for mcw.
            if ('multiColumnWizard' !== $property->getWidgetType()) {
                continue;
            }

            // Get the extra and make an own field from it.
            $config = $property->getExtra();

            // If we have no data here, go to the next.
            if(empty($config['columnFields']) || !is_array($config['columnFields'])){
                continue;
            }

            foreach ($config['columnFields'] as $fieldKey => $fieldConfig) {
                // Build the default name.
                $name = sprintf('%s__%s', $property->getName(), $fieldKey);
                if (true === $properties->hasProperty($name)) {
                    continue;
                }

                // Make a new field and fill it with the data from the config.
                $subProperty = new DefaultProperty($name);
                foreach ($fieldConfig as $key => $value) {
                    switch ($key) {
                        case 'label':
                            $subProperty->setLabel($value);
                            break;

                        case 'description':
                            if (!$subProperty->getDescription()) {
                                $subProperty->setDescription($value);
                            }
                            break;

                        case 'default':
                            if (!$subProperty->getDefaultValue()) {
                                $subProperty->setDefaultValue($value);
                            }
                            break;

                        case 'exclude':
                            $subProperty->setExcluded((bool)$value);
                            break;

                        case 'search':
                            $subProperty->setSearchable((bool)$value);
                            break;

                        case 'filter':
                            $subProperty->setFilterable((bool)$value);
                            break;

                        case 'inputType':
                            $subProperty->setWidgetType($value);
                            break;

                        case 'options':
                            $subProperty->setOptions($value);
                            break;

                        case 'explanation':
                            $subProperty->setExplanation($value);
                            break;

                        case 'eval':
                            $subProperty->setExtra(
                                array_merge(
                                    (array) $subProperty->getExtra(),
                                    (array) $value
                                )
                            );
                            break;

                        case 'reference':
                            $subProperty->setExtra(
                                array_merge(
                                    (array) $subProperty->getExtra(),
                                    array('reference' => &$value['reference'])
                                )
                            );
                            break;

                        default:
                    }
                }

                // Add all to the current list.
                $properties->addProperty($subProperty);
            }
        }

    }
}
