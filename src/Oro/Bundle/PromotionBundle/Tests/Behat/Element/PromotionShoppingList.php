<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ShoppingList;

class PromotionShoppingList extends ShoppingList implements DiscountSubtotalAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDiscountSubtotal()
    {
        return $this->getElement('PromotionShoppingListDiscountSubtotal')->getText();
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('PromotionShoppingListLineItem');
    }
}
