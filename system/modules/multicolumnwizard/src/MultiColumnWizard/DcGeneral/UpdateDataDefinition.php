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

use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\DefaultProperty;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PropertiesDefinitionInterface;
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
    public function addMcwFields(BuildDataDefinitionEvent $event)
    {
        // Get the container and all properties.
        $container = $event->getContainer();
        $properties = $container->getPropertiesDefinition();

        /** @var DefaultProperty $property */
        foreach ($properties as $property) {
            // Only run for mcw.
            if ('multiColumnWizard' !== $property->getWidgetType()) {
                continue;
            }

            // Get the extra and make an own field from it.
            $extra = $property->getExtra();

            // If we have no data here, go to the next.
            if(empty($extra['columnFields']) || !is_array($extra['columnFields'])){
                continue;
            }

            $this->addPropertyToDefinition($extra, $property, $properties);
        }
    }

    private function addPropertyToDefinition(
        array $extra,
        PropertyInterface $property,
        PropertiesDefinitionInterface $properties
    ) {
        foreach ($extra['columnFields'] as $fieldKey => $fieldConfig) {
            // Build the default name.
            $name = sprintf('%s__%s', $property->getName(), $fieldKey);

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
                        $subProperty->setExcluded((bool) $value);
                        break;

                    case 'search':
                        $subProperty->setSearchable((bool) $value);
                        break;

                    case 'filter':
                        $subProperty->setFilterable((bool) $value);
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
            $this->addSubMultiColumnWizardProperty($subProperty, $properties);
        }
    }

    private function addSubMultiColumnWizardProperty(
        PropertyInterface $property,
        PropertiesDefinitionInterface $properties
    ) {
        $extra = $property->getExtra();

        if (empty($extra['columnFields']) || !is_array($extra['columnFields'])) {
            return;
        }

        $this->addPropertyToDefinition($extra, $property, $properties);
    }
}
