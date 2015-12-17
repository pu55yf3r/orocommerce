<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Model\Action\BuildWebsiteCacheAction;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class BuildWebsiteCacheActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;
    /**
     * @var CacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;
    /**
     * @var BuildWebsiteCacheAction
     */
    protected $action;
    protected function setUp()
    {
        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->cacheBuilder = $this->getMock(
            'OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface'
        );
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $contextAccessor = new ContextAccessor();
        $this->action = new BuildWebsiteCacheAction($contextAccessor);
        $this->action->setRegistry($this->registry);
        $this->action->setCacheBuilder($this->cacheBuilder);
        $this->action->setDispatcher($eventDispatcher);
    }
    public function testExecute()
    {
        $entity = new Website();
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('beginTransaction');
        $entityManager->expects($this->any())
            ->method('commit');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($entityManager);
        $this->cacheBuilder->expects($this->once())
            ->method('buildCache')
            ->with($entity);
        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $entity]));
    }
}
