<?php

namespace Oro\Bundle\CouponBundle\Tests\Unit\Form\Type;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\CouponBundle\Entity\Coupon;
use Oro\Bundle\CouponBundle\Form\Type\CouponType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionSelectType;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CouponTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var CouponType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CouponType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var \PHPUnit_Framework_MockObject_MockObject|Translator $translator */
        $translator = $this->createMock(Translator::class);

        $promotionSelectType = new EntityType(
            [
                'promotion1' => $this->getEntity(Promotion::class, ['id' => 1]),
                'promotion2' => $this->getEntity(Promotion::class, ['id' => 2]),
            ],
            PromotionSelectType::NAME
        );

        return [
            new PreloadedExtension(
                [
                    $promotionSelectType->getName() => $promotionSelectType,
                ],
                [
                    'form' => [
                        new TooltipFormExtension($configProvider, $translator),
                    ],
                ]
            ),
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param array $submittedData
     * @param Coupon $expectedData
     */
    public function testSubmit($submittedData, Coupon $expectedData)
    {
        $form = $this->factory->create($this->formType);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var Coupon $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType, $this->createCoupon('test'));

        $this->assertTrue($form->has('code'));
        $this->assertTrue($form->has('promotion'));
        $this->assertTrue($form->has('usesPerUser'));
        $this->assertTrue($form->has('usesPerCoupon'));
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $promotion2 = $this->getEntity(Promotion::class, ['id' => 2]);

        return [
            'coupon with promotion' => [
                'submittedData' => [
                    'code' => 'test1234',
                    'promotion' => 'promotion2',
                    'usesPerUser' => 2,
                    'usesPerCoupon' => 3,
                ],
                'expectedData' => $this->createCoupon('test1234', 2, 3, $promotion2),
            ],
            'coupon without promotion' => [
                'submittedData' => [
                    'code' => 'test1234',
                    'promotion' => null,
                    'usesPerUser' => 2,
                    'usesPerCoupon' => 3,
                ],
                'expectedData' => $this->createCoupon('test1234', 2, 3),
            ],
        ];
    }

    /**
     * @param string $couponCode
     * @param int|null $usesPerUser
     * @param int|null $usesPerCoupon
     * @param Promotion $promotion
     * @return Coupon
     */
    public function createCoupon($couponCode, $usesPerUser = null, $usesPerCoupon = null, $promotion = null)
    {
        return (new Coupon())
            ->setCode($couponCode)
            ->setUsesPerUser($usesPerUser)
            ->setUsesPerCoupon($usesPerCoupon)
            ->setPromotion($promotion);
    }
}
