<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class OrderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['identifier', 'identifier-test-01'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['shippingAddress', new OrderAddress()],
            ['billingAddress', new OrderAddress()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['poNumber', 'PO-#1'],
            ['customerNotes', 'customer notes'],
            ['shipUntil', $now],
            ['currency', 'USD'],
            ['subtotal', 999.99],
            ['total', 999.99],
            ['paymentTerm', new PaymentTerm()],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['website', new Website()],
            ['shippingCost', new Price()]
        ];

        $order = new Order();
        $this->assertPropertyAccessors($order, $properties);
        $this->assertPropertyCollection($order, 'lineItems', new OrderLineItem());
    }

    public function testGetEmail()
    {
        $email = 'test@test.com';
        $order = new Order();
        $this->assertEmpty($order->getEmail());
        $accountUser = new AccountUser();
        $accountUser->setEmail($email);
        $order->setAccountUser($accountUser);
        $this->assertEquals($email, $order->getEmail());
    }

    public function testAccountUserToAccountRelation()
    {
        $order = new Order();

        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);

        $this->assertEmpty($order->getAccount());
        $order->setAccountUser($accountUser);
        $this->assertSame($account, $order->getAccount());
    }

    public function testPrePersist()
    {
        $order = new Order();
        $order->prePersist();
        $this->assertInstanceOf('\DateTime', $order->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $order = new Order();
        $order->preUpdate();
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }


    public function testPostLoad()
    {
        $item = new Order();

        $this->assertNull($item->getShippingCost());

        $value = 100;
        $currency = 'EUR';
        $this->setProperty($item, 'shippingCostAmount', $value)
            ->setProperty($item, 'shippingCostCurrency', $currency);

        $item->postLoad();

        $this->assertEquals(Price::create($value, $currency), $item->getShippingCost());
    }

    public function testUpdateShippingCost()
    {
        $item = new Order();
        $value = 1000;
        $currency = 'EUR';
        $item->setShippingCost(Price::create($value, $currency));

        $item->updateShippingCost();

        $this->assertEquals($value, $this->getProperty($item, 'shippingCostAmount'));
        $this->assertEquals($currency, $this->getProperty($item, 'shippingCostCurrency'));
    }

    public function testSetShippingEstimate()
    {
        $value = 10;
        $currency = 'USD';
        $price = Price::create($value, $currency);

        $item = new Order();
        $item->setShippingCost($price);

        $this->assertEquals($price, $item->getShippingCost());

        $this->assertEquals($value, $this->getProperty($item, 'shippingCostAmount'));
        $this->assertEquals($currency, $this->getProperty($item, 'shippingCostCurrency'));
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     *
     * @return OrderTest
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);

        return $this;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
