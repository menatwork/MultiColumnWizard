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

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\DefaultProperty;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;

/**
 * Class UpdateDataDefinition
 *
 * @author     Sven Baumann <baumann.sv@gmail.com> 2017
 * @package    MultiColumnWizard\DcGeneral
 */
class UpdateDataDefinition
{
    const POPULATE_PRIORITY     = -500000;
    const BUILD_WIDGET_PRIORITY = 500000;

    /**
     * Add all fields from the MCW to the DCA. This is needed for some fields, because other components need this
     * to create the widget/view etc.
     *
     * @param PopulateEnvironmentEvent $event
     *
     * @return void
     */
    public function addMcwFields(PopulateEnvironmentEvent $event)
    {
        $inputPropertyName = $this->getInputPropertyName($event->getEnvironment());

        // If the property don´t find in post or get then do nothing. The property must only add for popups.
        if (false === (bool) $inputPropertyName) {
            return;
        }

        // Add the sub properties of MCW in modal view or in ajax call.
        $this->buildProperty($inputPropertyName, $event->getEnvironment());
    }

    /**
     * Set the used modal value.
     * By Widget::getPost the post key split in parts, then don`t find the return value from modal.
     *
     * @param BuildWidgetEvent $event The event.
     *
     * @return void
     */
    public function setModalValue(BuildWidgetEvent $event)
    {
        $environment    = $event->getEnvironment();
        $sessionStorage = $environment->getSessionStorage();
        $session        = (array) $sessionStorage->get('MCW_MODAL_UPDATE');

        if (false === isset($session[md5($event->getProperty()->getName())])) {
            return;
        }

        $input = $environment->getInputProvider();
        $data = $session[md5($event->getProperty()->getName())];

        $input->setValue($data['key'], $input->getValue($data['valueFrom']));

        unset($session[md5($event->getProperty()->getName())]);
        $sessionStorage->set('MCW_MODAL_UPDATE', $session);
    }

    /**
     * Build the MCW column property and add to properties.
     *
     * @param string               $propertyName The property name.
     *
     * @param EnvironmentInterface $environment  The environment.
     *
     * @return void
     */
    private function buildProperty($propertyName, EnvironmentInterface $environment)
    {
        $dataDefinition = $environment->getDataDefinition();
        $properties     = $dataDefinition->getPropertiesDefinition();

        /** @var DefaultProperty $property */
        foreach ($properties as $property) {
            // Only run for mcw.
            if ('multiColumnWizard' !== $property->getWidgetType()) {
                continue;
            }

            // Get the extra and make an own field from it.
            $config = $property->getExtra();

            // If we have no data here, go to the next.
            if (empty($config['columnFields']) || !is_array($config['columnFields'])) {
                continue;
            }

            foreach ($config['columnFields'] as $fieldKey => $fieldConfig) {
                if ((true === $properties->hasProperty($propertyName))
                    || (false === strpos($propertyName, $property->getName()))
                    || ('[' . $fieldKey . ']' !== substr($propertyName, -strlen('[' . $fieldKey . ']')))
                ) {
                    continue;
                }

                // Make a new field and fill it with the data from the config.
                $subProperty = new DefaultProperty($propertyName);
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

                $properties->addProperty($subProperty);

                $subExtra = $subProperty->getExtra();

                // Fore some widgets must declare the evaluation in the data container configuration.
                $GLOBALS['TL_DCA'][$dataDefinition->getName()]['fields'][$subProperty->getName()]['eval'] = $subExtra;
            }
        }
    }

    /**
     * Get the input property from input.
     *
     * @param EnvironmentInterface $environment The environment.
     *
     * @return string
     */
    private function getInputPropertyName(EnvironmentInterface $environment)
    {
        $input = $environment->getInputProvider();

        if ((true === $input->hasParameter('field'))
            && (false === count($input->getParameter('field')))
        ) {
            return $input->getParameter('field');
        }
        if ((true === $input->hasValue('name'))
            && (false === count($input->getValue('name')))
        ) {
            $sessionStorage = $environment->getSessionStorage();
            $session        = $sessionStorage->get('MCW_MODAL_UPDATE');

            $chunks = explode(
                '::::',
                str_replace(
                    array('][', '[', ']'),
                    array('::::', '::::', ''),
                    $input->getValue('name')
                )
            );

            $session[md5($input->getValue('name'))] = array(
                'key'       => array_shift($chunks),
                'valueFrom' => $input->getValue('name')
            );

            $sessionStorage->set('MCW_MODAL_UPDATE', $session);

            return $input->getValue('name');
        }

        return '';
    }
}
