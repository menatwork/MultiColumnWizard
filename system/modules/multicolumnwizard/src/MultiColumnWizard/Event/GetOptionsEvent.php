<?php

/**
 * Contao Open Source CMS
 *
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  CyberSpectrum 2014
 * @package    MultiColumnWizard
 * @license    LGPL-3+
 * @filesource
 */

namespace MultiColumnWizard\Event;

use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired when a MultiColumnWizard wants to retrieve the options for a sub widget in dc-general context.
 */
class GetOptionsEvent extends Event
{
    const NAME = 'men-at-work.multi-column-wizard.get-options';

    /**
     * The name of the multi column wizard.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * The name of the sub widget.
     *
     * @var string
     */
    protected $subPropertyName;

    /**
     * The environment in use.
     *
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * The current model.
     *
     * @var ModelInterface
     */
    protected $model;

    /**
     * The multi column wizard.
     *
     * @var \MultiColumnWizard
     */
    protected $widget;

    /**
     * The options array.
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new instance.
     *
     * @param string               $propertyName    The name of the multi column wizard widget.
     *
     * @param string               $subPropertyName The name of the sub widget.
     *
     * @param EnvironmentInterface $environment     The environment instance.
     *
     * @param ModelInterface       $model           The current model.
     *
     * @param \MultiColumnWizard   $widget          The multi column wizard instance.
     *
     * @param array                $options         The current options (defaults to empty array).
     */
    public function __construct(
        $propertyName,
        $subPropertyName,
        EnvironmentInterface $environment,
        ModelInterface $model,
        \MultiColumnWizard $widget,
        $options = array()
    ) {
        $this->propertyName    = $propertyName;
        $this->subPropertyName = $subPropertyName;
        $this->environment     = $environment;
        $this->model           = $model;
        $this->widget          = $widget;
        $this->options         = $options;
    }

    /**
     * Retrieve the name of the multi column wizard property.
     *
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Retrieve the name of the property within the multi column wizard.
     *
     * @return string
     */
    public function getSubPropertyName()
    {
        return $this->subPropertyName;
    }

    /**
     * Retrieve the dc-general environment.
     *
     * @return EnvironmentInterface
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Retrieve the model in dc-general scope.
     *
     * @return ModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Retrieve the multi column wizard instance emitting the event.
     *
     * @return \MultiColumnWizard
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * Retrieve the options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the options.
     *
     * @param array $options The options.
     *
     * @return GetOptionsEvent
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
