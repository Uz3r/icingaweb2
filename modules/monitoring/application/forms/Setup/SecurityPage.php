<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;
use Icinga\Module\Monitoring\Forms\Config\SecurityConfigForm;

class SecurityPage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_security');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => $this->translate('Monitoring Security', 'setup.page.title'),
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
                    'To protect your monitoring environment against prying eyes please fill out the settings below.'
                )
            )
        );

        $securityConfigForm = new SecurityConfigForm();
        $securityConfigForm->createElements($formData);
        $this->addElements($securityConfigForm->getElements());
    }
}
