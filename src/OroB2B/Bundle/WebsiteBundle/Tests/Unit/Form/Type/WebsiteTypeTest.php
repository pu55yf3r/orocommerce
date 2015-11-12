<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;

class WebsiteTypeTest extends FormIntegrationTestCase
{
    /** @var  WebsiteType $type */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formType = new WebsiteType();
    }

    /**
     * @param bool $isValid
     * @param mixed $defaultData
     * @param array $submittedData
     * @param mixed $expectedData
     * @param array $options
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $defaultData, $submittedData, $expectedData, array $options = [])
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);
        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function testGetName()
    {
        $this->assertEquals(WebsiteType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'website_valid' => [
                'isValid'       => true,
                'defaultData'   => ['name' => 'OroCRM', 'url' => 'http://www.orocrm.com/'],
                'submittedData' => [
                    'name' => 'OroCommerce',
                    'url' => 'http://www.orocommerce.com/'
                ],
                'expectedData'  => ['name' => 'OroCommerce', 'url' => 'http://www.orocommerce.com/']
            ],
            'website_invalid_name' => [
                'isValid'       => false,
                'defaultData'   => ['name' => 'OroCommerce', 'url' => 'http://www.orocommerce.com/'],
                'submittedData' => [
                    'name' => '',
                    'url' => 'http://www.orocommerce.com/'
                ],
                'expectedData'  => ['name' => '', 'url' => 'http://www.orocommerce.com/'],
            ],
            'website_invalid_url' => [
                'isValid'       => false,
                'defaultData'   => ['name' => 'OroCommerce', 'url' => 'http://www.orocommerce.com/'],
                'submittedData' => [
                    'name' => 'OroCommerce',
                    'url' => 'orocommerce.com'
                ],
                'expectedData'  => ['name' => 'OroCommerce', 'url' => 'orocommerce.com'],
            ],
            'website_empty_url' => [
                'isValid'       => false,
                'defaultData'   => ['name' => 'OroCommerce', 'url' => 'http://www.orocommerce.com/'],
                'submittedData' => [
                    'name' => 'OroCommerce',
                    'url' => ''
                ],
                'expectedData'  => ['name' => 'OroCommerce', 'url' => ''],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
