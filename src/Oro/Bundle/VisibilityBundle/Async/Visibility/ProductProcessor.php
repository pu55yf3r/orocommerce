<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Resolves visibility for a product when its category is changed.
 */
class ProductProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var CacheBuilderInterface */
    private $cacheBuilder;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $logger
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CHANGE_PRODUCT_CATEGORY];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!isset($body['id'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(ProductVisibilityResolved::class);
        $em->beginTransaction();
        try {
            if ($this->cacheBuilder instanceof ProductCaseCacheBuilderInterface) {
                $productIds = array_unique((array) $body['id']);
                $products = $this->getProducts($productIds);
                foreach ($products as $product) {
                    $this->cacheBuilder->productCategoryChanged($product, $body['scheduleReindex'] ?? false);
                }
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @param int[] $productIds
     *
     * @return Product[]
     *
     * @throws EntityNotFoundException if a product does not exist
     */
    private function getProducts(array $productIds): array
    {
        /** @var Product[] $products */
        $products = $this->doctrine->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->findBy(['id' => $productIds]);
        if (count($productIds) !== count($products)) {
            throw new EntityNotFoundException('Product was not found.');
        }

        return $products;
    }
}
