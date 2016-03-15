<?php

namespace OroB2B\Bundle\CheckoutBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\ActionBundle\Exception\InvalidParameterException;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class StartCheckout extends AbstractAction
{
    const SOURCE = 'source';
    const SOURCE_DATA = 'sourceData';
    const WORKFLOW_NAME = 'b2b_flow_checkout';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * StartCheckout constructor.
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     * @param TokenStorageInterface $tokenStorage
     * @param PropertyAccessor $propertyAccessor
     * @param WorkflowManager $workflowManager
     * @param RouterInterface $router
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        WebsiteManager $websiteManager,
        TokenStorageInterface $tokenStorage,
        PropertyAccessor $propertyAccessor,
        WorkflowManager $workflowManager,
        RouterInterface $router
    ) {
        parent::__construct($contextAccessor);
        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
        $this->tokenStorage = $tokenStorage;
        $this->workflowManager = $workflowManager;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::SOURCE])) {
            throw new InvalidParameterException('Source parameter is required');
        }

        if (empty($options[self::SOURCE_DATA])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        /** @var string $sourceFieldName */
        $sourceFieldName = $this->contextAccessor->getValue($context, $this->options[self::SOURCE]);
        /** @var object $sourceEntity */
        $sourceEntity = $this->contextAccessor->getValue($context, $this->options[self::SOURCE_DATA]);
        $checkoutEntityManager = $this->registry->getManagerForClass('OroB2BCheckoutBundle:Checkout');
        $checkoutSource = $this->registry
            ->getManagerForClass('OroB2BCheckoutBundle:CheckoutSource')
            ->getRepository('OroB2BCheckoutBundle:CheckoutSource')
            ->findOneBy([$sourceFieldName => $sourceEntity]);
        if (!$checkoutSource) {
            /** @var AccountUser $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $checkoutSource = new CheckoutSource();
            $this->propertyAccessor->setValue($checkoutSource, $sourceFieldName, $sourceEntity);
            $checkout = new Checkout();
            $checkout->setSource($checkoutSource);
            $checkout->setAccountUser($user);
            $checkout->setWebsite($this->websiteManager->getCurrentWebsite());
            $account = $user->getAccount();
            $checkout->setAccount($account);
            $checkout->setOwner($account->getOwner());
            $checkout->setOrganization($account->getOrganization());
            $this->addWorkflowItemDataSettings($context, $checkout);
            $this->addCheckoutProperties($context, $checkout);
            $checkoutEntityManager->persist($checkout);
            $checkoutEntityManager->flush();
            $this->workflowManager->startWorkflow(self::WORKFLOW_NAME, $checkout);
        } else {
            $checkout = $checkoutEntityManager
                ->getRepository('OroB2BCheckoutBundle:Checkout')
                ->findOneBy(['source' => $checkoutSource]);
        }

        $url = $this->router->generate('orob2b_checkout_frontend_checkout', ['id' => $checkout->getId()]);
        $this->contextAccessor->setValue($context, new PropertyPath('redirectUrl'), $url);
    }

    /**
     * @param mixed $context
     * @param Checkout $checkout
     * @throws WorkflowException
     */
    protected function addWorkflowItemDataSettings($context, Checkout $checkout)
    {
        if ($this->contextAccessor->hasValue($context, $this->options['settings'])) {
            $settings = $this->contextAccessor->getValue($context, $this->options['settings']);
            $workflowData = $checkout->getWorkflowItem()->getData();
            foreach ($settings as $key => $setting) {
                $workflowData->set($key, $setting);
            }
        }
    }

    /**
     * @param mixed $context
     * @param Checkout $checkout
     */
    protected function addCheckoutProperties($context, Checkout $checkout)
    {
        if ($this->contextAccessor->hasValue($context, $this->options['data'])) {
            /** @var array $data */
            $data = $this->contextAccessor->getValue($context, $this->options['data']);
            foreach ($data as $property => $value) {
                $this->propertyAccessor->setValue($checkout, $property, $value);
            }
        }
    }
}
