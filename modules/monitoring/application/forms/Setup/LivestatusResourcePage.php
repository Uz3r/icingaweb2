<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;
use Icinga\Forms\Config\Resource\LivestatusResourceForm;

class LivestatusResourcePage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_livestatus');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'hidden',
            'type',
            array(
                'required'  => true,
                'value'     => 'livestatus'
            )
        );
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => $this->translate('Monitoring Livestatus Resource', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => $this->translate(
                    'Please fill out the connection details below to access the Livestatus'
                    . ' socket interface for your monitoring environment.'
                )
            )
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        } else {
            $this->addElement(
                'hidden',
                'skip_validation',
                array(
                    'required'  => true,
                    'value'     => 0
                )
            );
        }

        $livestatusResourceForm = new LivestatusResourceForm();
        $this->addElements($livestatusResourceForm->createElements($formData)->getElements());
        $this->getElement('name')->setValue('icinga_livestatus');
    }

    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if (false === isset($data['skip_validation']) || $data['skip_validation'] == 0) {
            if (false === LivestatusResourceForm::isValidResource($this)) {
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Add a checkbox to the form by which the user can skip the connection validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'required'      => true,
                'label'         => $this->translate('Skip Validation'),
                'description'   => $this->translate(
                    'Check this to not to validate connectivity with the given Livestatus socket'
                )
            )
        );
    }
}
