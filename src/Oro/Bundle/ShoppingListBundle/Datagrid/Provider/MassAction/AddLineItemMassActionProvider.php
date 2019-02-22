<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AddLineItemMassActionProvider implements MassActionProviderInterface
{
    const NAME_PREFIX = 'oro_shoppinglist_frontend_addlineitem';

    use FeatureCheckerHolderTrait;

    /**
     * @var ShoppingListManager
     */
    protected $manager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param ShoppingListManager $manager
     * @param TranslatorInterface $translator
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        ShoppingListManager $manager,
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage
    ) {
        $this->manager = $manager;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        $actions = [];

        if ($this->isGuestCustomerUser()) {
            $actions['current'] = $this->getConfig([
                'label' => $this->translator->trans('oro.shoppinglist.actions.add_to_current_shopping_list'),
                'is_current' => true
            ]);
        } else {
            if (!$this->isAllowed()) {
                return [];
            }

            $shoppingLists = $this->manager->getShoppingLists(['list.id' => Criteria::ASC]);

            /** @var ShoppingList $shoppingList */
            foreach ($shoppingLists as $shoppingList) {
                $name = 'list' . $shoppingList->getId();

                $actions[$name] = $this->getConfig([
                    'label' => $this->getLabel($shoppingList),
                    'route_parameters' => [
                        'shoppingList' => $shoppingList->getId(),
                    ],
                ]);
            }
        }

        if ($this->isFeaturesEnabled()) {
            $actions['new'] = $this->getConfig([
                'type' => 'window',
                'label' => $this->translator->trans('oro.shoppinglist.product.create_new_shopping_list.label'),
                'icon' => 'plus',
                'route' => 'oro_shopping_list_add_products_to_new_massaction',
                'frontend_handle' => 'shopping-list-create',
                'frontend_options' => [
                    'title' => $this->translator->trans('oro.shoppinglist.product.add_to_shopping_list.label'),
                    'regionEnabled' => false,
                    'incrementalPosition' => false,
                    'dialogOptions' => [
                        'modal' => true,
                        'resizable' => false,
                        'width' => 480,
                        'autoResize' => true,
                        'dialogClass' => 'shopping-list-dialog',
                    ],
                    'alias' => 'add_products_to_new_shopping_list_mass_action',
                ],
            ]);
        }

        return $actions;
    }

    /**
     * @return array
     */
    public function getFormattedActions()
    {
        $massActions = $this->getActions();
        $formattedMassActions = [];
        foreach ($massActions as $title => $massAction) {
            $formattedMassActions[self::NAME_PREFIX . $title] = $massAction;
        }

        return $formattedMassActions;
    }

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getConfig($options)
    {
        return array_merge([
            'type' => 'addproducts',
            'icon' => 'shopping-cart',
            'data_identifier' => 'product.id',
            'frontend_type' => 'add-products-mass',
            'handler' => 'oro_shopping_list.mass_action.add_products_handler',
            'is_current' => false
        ], $options);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return string
     */
    protected function getLabel(ShoppingList $shoppingList)
    {
        return $this->translator->trans(
            'oro.shoppinglist.actions.add_to_shopping_list',
            [
                '{{ shoppingList }}' => \strip_tags($shoppingList->getLabel())
            ]
        );
    }

    /**
     * @return bool
     */
    private function isGuestCustomerUser(): bool
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }

    /**
     * @return bool
     */
    private function isAllowed(): bool
    {
        return $this->authorizationChecker->isGranted('oro_shopping_list_frontend_update');
    }
}
