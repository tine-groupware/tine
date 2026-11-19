<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Sales_Controller_Product
 */
class Sales_ProductControllerTest extends TestCase
{
    protected ?string $_uniq = null;

    /**
     * unique marker in the product names - the searches must not find other products (e.g. demo data)
     */
    protected function _getUniq(): string
    {
        return $this->_uniq ??= Tinebase_Record_Abstract::generateUID(8);
    }

    protected function _productFilter(array $filters): Tinebase_Model_Filter_FilterGroup
    {
        $filters[] = ['field' => 'name', 'operator' => 'contains', 'value' => $this->_getUniq()];
        return Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, $filters);
    }

    protected function _localizedName(string $text): array
    {
        return [[
            Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'en',
            Tinebase_Record_PropertyLocalization::FLD_TEXT => $text . ' ' . $this->_getUniq(),
        ]];
    }

    /**
     * lazy init of uit
     *
     * @return Sales_Controller_Product
     */
    public function getUit()
    {
        if ($this->_uit === null) {
            $this->_uit = Sales_Controller_Product::getInstance();
        }
        
        return $this->_uit;
    }
    
    /**
     * 
     * @return Sales_Model_Contract
     */
    public function testCreateProduct()
    {
        $data = new Sales_Model_Product(array(
            'name' => [[
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'en',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => 'A new product ' . $this->_getUniq(),
            ], [
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'de',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => 'Ein neues Produkt ' . $this->_getUniq(),
            ]],
        ));
        $product = $this->getUit()->create($data);
        
        $this->assertNotEmpty($product->number);
        
        return $product;
    }

    public function testOrderByName()
    {
        $product = $this->testCreateProduct();
        $product1 = $this->getUit()->create(new Sales_Model_Product(array(
            'name' => [[
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'en',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => 'B rod ' . $this->_getUniq(),
            ], [
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'de',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => 'C rod ' . $this->_getUniq(),
            ]],
        )));

        $result = $this->getUit()->search($this->_productFilter([
                ['field' => 'query', 'operator' => 'contains', 'value' => 'product']
            ]), new Tinebase_Model_Pagination(['sort' => 'name']));
        $this->assertSame(1, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
            ['field' => 'query', 'operator' => 'contains', 'value' => 'rod']
        ]), new Tinebase_Model_Pagination(['sort' => 'name']));
        $this->assertSame(2, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());
        $this->assertSame($product1->getId(), $result->getLastRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
            ['field' => 'query', 'operator' => 'contains', 'value' => 'rod']
        ]), new Tinebase_Model_Pagination(['sort' => 'name', 'dir' => 'desc']));
        $this->assertSame(2, $result->count());
        $this->assertSame($product1->getId(), $result->getFirstRecord()->getId());
        $this->assertSame($product->getId(), $result->getLastRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
            ['field' => 'query', 'operator' => 'contains', 'value' => 'rod', 'clientOptions' => ['language' => 'de']]
        ]), new Tinebase_Model_Pagination(['sort' => 'name']));
        $this->assertSame(2, $result->count());
        $this->assertSame($product1->getId(), $result->getFirstRecord()->getId());
        $this->assertSame($product->getId(), $result->getLastRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
            ['field' => 'query', 'operator' => 'contains', 'value' => 'rod', 'clientOptions' => ['language' => 'de']]
        ]), new Tinebase_Model_Pagination(['sort' => 'name', 'dir' => 'desc']));
        $this->assertSame(2, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());
        $this->assertSame($product1->getId(), $result->getLastRecord()->getId());
    }

    public function testQueryFilterReLocalization()
    {
        $product = $this->testCreateProduct();
        $result = $this->getUit()->search($this->_productFilter([
                ['field' => 'query', 'operator' => 'contains', 'value' => 'product']
            ]));
        $this->assertSame(1, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
                ['field' => 'query', 'operator' => 'contains', 'value' => 'Produkt']
            ]));
        $this->assertSame(1, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
                ['field' => 'query', 'operator' => 'contains', 'value' => 'rod']
            ]));
        $this->assertSame(1, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());
    }

    public function testNameFilterReLocalization()
    {
        $product = $this->testCreateProduct();
        $result = $this->getUit()->search($this->_productFilter([
                ['field' => 'name', 'operator' => 'contains', 'value' => 'product']
            ]));
        $this->assertSame(1, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
                ['field' => 'name', 'operator' => 'contains', 'value' => 'Produkt']
            ]));
        $this->assertSame(1, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());

        $result = $this->getUit()->search($this->_productFilter([
                ['field' => 'name', 'operator' => 'contains', 'value' => 'rod']
            ]));
        $this->assertSame(1, $result->count());
        $this->assertSame($product->getId(), $result->getFirstRecord()->getId());
    }
    
    /**
     * testUpdateProductLifespan
     * 
     * @see 0010766: set product lifespan
     */
    public function testUpdateProductLifespan()
    {
        $product1 = $this->getUit()->create(new Sales_Model_Product(array(
            'name' => $this->_localizedName('product activates in future'),
            'lifespan_start' => Tinebase_DateTime::now()->addDay(1)
        )));
        $product2 = $this->getUit()->create(new Sales_Model_Product(array(
            'name' => $this->_localizedName('product lifespan ended'),
            'lifespan_end' => Tinebase_DateTime::now()->subDay(1)
        )));
        $product3 = $this->getUit()->create(new Sales_Model_Product(array(
            'is_active' => 0,
            'name' => $this->_localizedName('product lifespan started'),
            'lifespan_start' => Tinebase_DateTime::now()->subDay(1)
        )));
        $product4 = $this->getUit()->create(new Sales_Model_Product(array(
            'is_active' => 0,
            'name' => $this->_localizedName('product lifespan not yet ended'),
            'lifespan_end' => Tinebase_DateTime::now()->addDay(1)
        )));
        
        $productsToTest = array(
            array('expectedIsActive' => 0, 'product' => $product1),
            array('expectedIsActive' => 0, 'product' => $product2),
            array('expectedIsActive' => 1, 'product' => $product3),
            array('expectedIsActive' => 1, 'product' => $product4),
        );
        
        $this->getUit()->updateProductLifespan();
        
        foreach ($productsToTest as $product) {
            $updatedProduct = $this->getUit()->get($product['product']);
            $this->assertEquals($product['expectedIsActive'], $updatedProduct->is_active, print_r($product['product']->toArray(), true));
        }
    }

    public function testDeleteProduct()
    {
        $product = $this->testCreateProduct();
        $this->getUit()->delete([$product->getId()]);
        $result = $this->getUit()->search($this->_productFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => $product->getId()]
        ]));
        $this->assertSame(0, $result->count());
    }
}
