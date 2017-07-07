@fixture-OroProductBundle:product_collection_add.yml
Feature:
  In order to add more than one product by some criteria into the content nodes
  As an Administrator
  I want to have ability of adding Product Collection variant

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
     | Admin  | first_session  |
     | Buyer  | second_session |

  Scenario: Add Product Collection variant
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    Then I should see 1 element "Product Collection Variant Label"
    And I should see 1 element "Segment Name With Placeholder"
    And I should see an "Product Collection Preview Grid" element
    And I should see "No records found"

  Scenario: Use Advanced Filter
    When I click "Content Variants"
    And I click on "Advanced Filter"
    And I should see "DRAG TO SELECT"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU1" in "value"
    And I click on "Preview Results"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |

  Scenario: Save Product Collection with defined filters and applied query
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I reload the page
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
    And I should see 1 element "Segment Name Without Placeholder"

  Scenario: Created Product Collection accessible at frontend
    Given I operate as the Buyer
    And I am on homepage
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And Page title equals to "CollectionMetaTitle"
    And Page meta title equals "CollectionMetaTitle"
    And Page meta keywords equals "CollectionMetaKeyword"
    And Page meta description equals "CollectionMetaDescription"

  Scenario: Autogenerated Product Collection segments are available in Manage Segments section
    Given I proceed as the Admin
    When I go to Reports & Segments/ Manage Segments
    Then I should see "Product Collection" in grid with following data:
      | Entity                  | Product               |
      | Type                    | Dynamic               |
    And I should see following actions for Product Collection in grid:
      | View                    |
      | Edit within Web Catalog |
    When I click on Product Collection in grid
    Then I should not see an "Entity Edit Button" element
    And I should not see an "Entity Delete Button" element
    And I should see an "Edit within Web Catalog" element
    When I click "Edit within Web Catalog"
    Then "Content Node Form" must contains values:
      | Titles | Root Node |
      | Meta Title | CollectionMetaTitle |
      | Meta Keywords | CollectionMetaKeyword |
      | Meta Description | CollectionMetaDescription |
