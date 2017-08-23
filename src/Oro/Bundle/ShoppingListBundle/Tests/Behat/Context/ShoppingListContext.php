<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\SubtotalAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class ShoppingListContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^I open page with shopping list (?P<shoppingListLabel>[\w\s]+)/
     * @When /^(?:|I )open page with shopping list "(?P<shoppingListLabel>[\w\s]+)"$/
     *
     * @param string $shoppingListLabel
     */
    public function openShoppingList($shoppingListLabel)
    {
        $shoppingList = $this->getShoppingList($shoppingListLabel);

        $this->visitPath($this->getUrl('oro_shopping_list_frontend_view', $shoppingList->getId()));
        $this->waitForAjax();
    }

    /**
     * @Given /^(?:|I )request a quote from shopping list "(?P<shoppingListLabel>[^"]+)" with data:$/
     *
     * @param string $shoppingListLabel
     * @param TableNode $table
     */
    public function iRequestAQuoteFromShoppingListWithData($shoppingListLabel, TableNode $table)
    {
        $this->openShoppingList($shoppingListLabel);

        $this->getPage()->findLink('Request Quote')->click();
        $this->waitForAjax();

        $form = $this->createElement('OroForm');
        $form->fill($table);
        $this->getPage()->pressButton('Submit Request');
    }

    /**
     * @Then /^(?:|I )see next subtotals for "(?P<elementName>[\w\s]+)":$/
     *
     * @param TableNode $expectedSubtotals
     * @param string $elementName
     */
    public function assertSubtotals(TableNode $expectedSubtotals, $elementName)
    {
        /** @var SubtotalAwareInterface $element */
        $element = $this->createElement($elementName);

        if (!$element instanceof SubtotalAwareInterface) {
            throw new \InvalidArgumentException(
                sprintf('Element "%s" expected to implement SubtotalsAwareInterface', $elementName)
            );
        }

        $rows = $expectedSubtotals->getRows();
        array_shift($rows);

        foreach ($rows as list($subtotalName, $subtotalAmount)) {
            static::assertEquals(
                $subtotalAmount,
                $element->getSubtotal($subtotalName),
                sprintf(
                    'Wrong value for "%s" subtotal. Expected "%s" got "%s"',
                    $subtotalName,
                    $subtotalAmount,
                    $element->getSubtotal($subtotalName)
                )
            );
        }
    }

    /**
     * @param string $path
     * @param int $id
     * @return string
     */
    protected function getUrl($path, $id)
    {
        return $this->getContainer()->get('router')->generate($path, ['id' => $id]);
    }

    /**
     * @param string $label
     * @return ShoppingList
     */
    protected function getShoppingList($label)
    {
        return $this->getRepository(ShoppingList::class)->findOneBy(['label' => $label]);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository($className);
    }
}
