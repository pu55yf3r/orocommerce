<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolation
 */
class AjaxOrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );
    }

    public function testNewOrderSubtotals()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_create'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertTotals($crawler);
    }

    public function testSubtotals()
    {
        $order = $this->getReference(LoadOrders::MY_ORDER);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_frontend_update', ['id' => $order->getId()])
        );
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertTotals($crawler, $order->getId());
    }

    /**
     * @param Crawler $crawler
     * @param null|int $id
     */
    protected function assertTotals(Crawler $crawler, $id = null)
    {
        $form = $crawler->selectButton('Save and Close')->form();

        $form->getFormNode()->setAttribute(
            'action',
            $this->getUrl('orob2b_pricing_frontend_entity_totals', [
                'entityId' => $id,
                'entityClassName' => ClassUtils::getClass(new Order())
            ])
        );


        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('subtotals', $data);
        $this->assertArrayHasKey('total', $data);
    }
}
