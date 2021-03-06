<?php
/**
 * Athene2 - Advanced Learning Resources Manager
 *
 * @author      Aeneas Rekkas (aeneas.rekkas@serlo.org)
 * @license     MIT License
 * @license     http://opensource.org/licenses/MIT The MIT License (MIT)
 * @link        https://github.com/serlo-org/athene2 for the canonical source repository
 */
namespace Ads\Form;

use Attachment\Form\AttachmentFieldset;
use Attachment\Form\AttachmentFieldsetProvider;
use Zend\Form\Element\Select;
use Zend\Form\Element\Submit;
use Zend\Form\Element\Text;
use Zend\Form\Element\Textarea;
use Zend\Form\Element\Url;
use Zend\Form\Form;

class AdForm extends Form implements AttachmentFieldsetProvider
{

    public function __construct()
    {
        parent::__construct('createAd');
        $this->setAttribute('class', 'clearfix');
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');

        $this->add((new Text('title'))->setLabel('Title:'))->setAttribute('required', 'required');
        $this->add((new Url('url'))->setLabel('Url:'))->setAttribute('required', 'required');
        $this->add((new Textarea('content'))->setLabel('Content:'))->setAttribute('required', 'required');
        $this->add(
            (new Select('frequency'))->setValueOptions(
                [
                    '0' => 'Never',
                    '1' => 'Less',
                    '2' => 'Normal',
                    '3' => 'More'
                ]
            )->setAttribute('required', 'required')->setLabel('frequency')
        );
        $this->add(new AttachmentFieldset(false));
        $this->add(
            (new Submit('submit'))->setValue('Save')->setAttribute('class', 'btn btn-success pull-right')
        );

        $filter = new AdFilter();
        $this->setInputFilter($filter);
    }
}
