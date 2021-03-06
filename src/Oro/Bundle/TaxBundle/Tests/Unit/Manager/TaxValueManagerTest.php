<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\EventListener\DoctrineFlushProgressListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxValueManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const TAX_VALUE_CLASS = TaxValue::class;
    const TAX_CLASS = Tax::class;

    /** @var TaxValueManager */
    private $manager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var DoctrineFlushProgressListener|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineFlushProgressListener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineFlushProgressListener = $this->createMock(DoctrineFlushProgressListener::class);

        $this->manager = new TaxValueManager(
            $this->doctrineHelper,
            $this->doctrineFlushProgressListener,
            self::TAX_VALUE_CLASS,
            self::TAX_CLASS
        );
    }

    public function testGetTaxValue()
    {
        $class = self::TAX_VALUE_CLASS;
        $id = 1;
        $taxValue = new TaxValue();

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(static::once())
            ->method('findOneBy')
            ->with(
                static::logicalAnd(
                    static::isType('array'),
                    static::containsEqual($class),
                    static::containsEqual($id)
                )
            )
            ->willReturn($taxValue);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);

        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));

        // cache
        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));
    }

    public function testGetTaxValueNew()
    {
        $class = self::TAX_VALUE_CLASS;
        $id = 1;

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(static::once())
            ->method('findOneBy')
            ->with(
                static::logicalAnd(
                    static::isType('array'),
                    static::containsEqual($class),
                    static::containsEqual($id)
                )
            )
            ->willReturn(null);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')->willReturn($repository);

        $taxValue = $this->manager->getTaxValue($class, $id);
        $this->assertInstanceOf(self::TAX_VALUE_CLASS, $taxValue);

        // cache
        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));
    }

    /**
     * @dataProvider saveTaxValueProvider
     * @param bool $flushInProgress
     */
    public function testSaveTaxValue(bool $flushInProgress)
    {
        $taxValue = new TaxValue();

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($taxValue);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($em);

        $this->doctrineFlushProgressListener->expects($this->once())
            ->method('isFlushInProgress')
            ->with($em)
            ->willReturn($flushInProgress);


        $classMetadata = $this->createMock(ClassMetadata::class);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->exactly((int)$flushInProgress))
            ->method('computeChangeSet')
            ->with($classMetadata, $taxValue);

        $em->expects($this->exactly((int)$flushInProgress))
            ->method('getClassMetadata')
            ->with(TaxValue::class)
            ->willReturn($classMetadata);

        $em->expects($this->exactly((int)$flushInProgress))
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->manager->saveTaxValue($taxValue);
    }

    /**
     * @return array
     */
    public function saveTaxValueProvider()
    {
        return [
            'flush in progress' => [
                'flushInProgress' => true,
            ],
            'flush not in progress' => [
                'flushInProgress' => false,
            ],
        ];
    }

    /**
     * @dataProvider flushTaxValueIfAllowedDataProvider
     * @param $flushInProgress
     * @param $flushExpected
     */
    public function testFlushTaxValueIfAllowed(bool $flushInProgress, bool $flushExpected)
    {
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->exactly((int)$flushExpected))->method('flush');

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($em);

        $this->doctrineFlushProgressListener->expects($this->once())
            ->method('isFlushInProgress')
            ->with($em)
            ->willReturn($flushInProgress);

        $this->manager->flushTaxValueIfAllowed();
    }

    /**
     * @return array
     */
    public function flushTaxValueIfAllowedDataProvider()
    {
        return [
            'flush not in progress' => [
                'flushInProgress' => false,
                'flushExpected' => true,
            ],
            'flush in progress' => [
                'flushInProgress' => true,
                'flushExpected' => false,
            ],
        ];
    }

    public function testProxyGetReference()
    {
        $code = 'code';

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())->method('findOneBy')->with(['code' => 'code']);

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')
            ->with(self::TAX_CLASS)->willReturn($repo);

        $this->manager->getTax($code);
    }

    public function testClear()
    {
        $class = 'stdClass';
        $id = 1;
        $cachedTaxValue = new TaxValue();
        $notCachedTaxValue = new TaxValue();

        $repository = $this->createMock(ObjectRepository::class);

        $repository->expects(static::exactly(2))
            ->method('findOneBy')
            ->with(
                static::logicalAnd(
                    static::isType('array'),
                    static::containsEqual($class),
                    static::containsEqual($id)
                )
            )
            ->willReturnOnConsecutiveCalls($cachedTaxValue, $notCachedTaxValue);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->assertSame($cachedTaxValue, $this->manager->getTaxValue($class, $id));
        $this->assertSame($cachedTaxValue, $this->manager->getTaxValue($class, $id));
        $this->manager->clear();
        $this->assertSame($notCachedTaxValue, $this->manager->getTaxValue($class, $id));
    }

    /**
     * @dataProvider removeTaxValueProvider
     * @param bool $flush
     * @param bool $contains
     * @param bool $expectedResult
     */
    public function testRemoveTaxValue($flush, $contains, $expectedResult)
    {
        $taxValue = new TaxValue();

        $taxValueEm = $this->createMock(EntityManager::class);

        $taxValueEm->expects($this->once())
            ->method('contains')
            ->with($taxValue)
            ->willReturn($contains);

        $taxValueEm->expects($contains ? $this->once() : $this->never())
            ->method('remove')
            ->with($taxValue);

        $taxValueEm->expects($contains && $flush ? $this->once() : $this->never())
            ->method('flush')
            ->with($taxValue);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($taxValueEm);

        $this->assertEquals($expectedResult, $this->manager->removeTaxValue($taxValue, $flush));
    }

    /**
     * @return array
     */
    public function removeTaxValueProvider()
    {
        return [
            [
                'flush' => true,
                'contains' => false,
                'expectedResult' => false,
            ],
            [
                'flush' => true,
                'contains' => true,
                'expectedResult' => true,
            ],
            [
                'flush' => false,
                'contains' => true,
                'expectedResult' => true,
            ],
        ];
    }

    public function testPreloadTaxValues()
    {
        $entityClass = 'SomeClass';
        $entityIds = [1, 2, 5];
        $taxValue1 = $this->getEntity(TaxValue::class, ['entityId' => 1]);
        $taxValue2 = $this->getEntity(TaxValue::class, ['entityId' => 5]);
        $taxValues = [$taxValue1, $taxValue2];

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['entityClass' => $entityClass, 'entityId' => $entityIds])
            ->willReturn($taxValues);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($repository);

        $this->manager->preloadTaxValues($entityClass, $entityIds);

        $this->assertEquals($taxValue1, $this->manager->getTaxValue($entityClass, 1));
        $this->assertEquals($taxValue2, $this->manager->getTaxValue($entityClass, 5));

        $this->manager->preloadTaxValues($entityClass, $entityIds); // Check cache
    }
}
