<?php
/**
 * /**
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this extension
 * to newer versions in the future.
 *
 * @category   Hackathon
 * @package    Hackathon_PromoCodeMessages
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include('SalesRuleMother.php');

/**
 * Integration-style tests to check error messages only; real cart rules are created to test, but the actual
 * validation will always fail so therefore there is no actual validation of cart rules, only error messaging.
 */
class Hackathon_PromoCodeMessages_Model_ValidatorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Mage_Sales_Model_Quote
     */
    private $quoteMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Mage_Core_Model_Store
     */
    private $storeMock;

    /**
     * @var Hackathon_PromoCodeMessages_Model_SalesRuleMother
     */
    private $ruleMother;

    /**
     * @var Mage_SalesRule_Model_Rule
     */
    private $rule;

    /**
     * @var false|Hackathon_PromoCodeMessages_Model_Validator|Mage_Core_Model_Abstract
     */
    private $validator;

    protected function setUp()
    {
        Mage::app('default');
        $this->quoteMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getCustomerGroupId', 'getCustomerId'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();

        $this->ruleMother = new Hackathon_PromoCodeMessages_Model_SalesRuleMother();
        $this->validator = Mage::getModel('hackathon_promocodemessages/validator');
    }

    protected function tearDown()
    {
        $this->storeMock = null;
        $this->quoteMock = null;
        $this->ruleMother = null;
        $this->validator = null;
        if ($this->rule) {
            $this->rule->delete();
        }
    }

    public function testValidateWithInvalidCode()
    {
        $couponCode = 'sdfsdf';
        $this->expectException(Mage_Core_Exception::class);
        $this->expectExceptionMessage('<ul class="promo_error_message">Coupon code does not exist.</ul>');
        $this->validator->validate($couponCode, $this->quoteMock);
    }

    public function testValidateInvalidWebsiteIds()
    {
//        $this->rule = $this->ruleMother->generateRule();
        $this->rule = $this->ruleMother->generateRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(400);

        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message">Your coupon is not valid for this store.</ul>'
            . '<ul class="promo_error_additional">Allowed Websites: Main Website.</ul>';
        $this->expectExceptionMessage($exceptionMsg);

        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testInvalidCustomerGroups()
    {
        $this->rule = $this->ruleMother->generateCustomerGroupIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(0);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message">Your coupon is not valid for your Customer '
            . 'Group.</ul><ul class="promo_error_additional">Allowed Customer Groups: General.</ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testInactiveRule()
    {
        $this->rule = $this->ruleMother->generateInactiveRule();
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message">Your coupon is inactive.</ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testExpiredRule()
    {
        $this->rule = $this->ruleMother->generateExpiredRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message">Your coupon is no longer valid. It expired on '
            . 'January 1, 2010.</ul><ul class="promo_error_additional"></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testNotYetActiveRule()
    {
        $this->rule = $this->ruleMother->generateNotYetActiveRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message">Your coupon is not valid yet. It will be active '
            . 'on January 1, 2030.</ul><ul class="promo_error_additional"></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testGloballyAlreadyUsedRule()
    {
        $this->rule = $this->ruleMother->generateGlobalAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message">Your coupon was already used.</ul>'
            . '<ul class="promo_error_additional">It may only be used 1 time(s).</ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testCustomerAlreadyUsedRule()
    {
        $this->rule = $this->ruleMother->generateCustomerAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message">Your coupon was already used.</ul>'
            . '<ul class="promo_error_additional">It may only be used 1 time(s).</ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsSubtotalRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionSubtotalRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Subtotal must be '
            . 'equal or greater than <em>$1,000.00</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsTotalQtyRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionTotalQtyRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Total Items '
            . 'Quantity must be <em>5</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsWeightRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionWeightRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Total Weight '
            . 'must be greater than <em>5 lbs</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsPaymentMethodRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionPaymentMethodRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Payment Method '
            . 'must be <em>Check / Money order</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsShippingMethodRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionShippingMethodRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Shipping Method '
            . 'must be <em>flatrate_flatrate</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsPostCodeRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionPostCodeRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Shipping Postcode '
            . 'must be one of <em>11215, 12346</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsRegionRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionRegionRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Shipping Region '
            . 'must be <em>Quebec</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsRegionIdRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionRegionIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Shipping '
            . 'State/Province must be <em>Alabama</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsCountryIdRule()
    {
        $this->rule = $this->ruleMother->generateAddressConditionCountryIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Shipping Country '
            . 'must be <em>United States</em>.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testProductConditionsCategoriesRule()
    {
        $this->rule = $this->ruleMother->generateProductConditionCategoriesRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_heading">All of the '
            . 'following conditions must be met:<ul><li class="promo_error_item">Category must be one '
            . 'of <em>Root Catalog, Default Category</em>.</li></ul></li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testProductConditionsSkuRule()
    {
        $this->rule = $this->ruleMother->generateFoundProductConditionAttributeRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_heading">All of the '
            . 'following conditions must be met:<ul><li class="promo_error_item">SKU must be '
            . '<em>msj000</em>.</li></ul></li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testProductConditionNotFoundSkuRule()
    {
        $this->rule = $this->ruleMother->generateNotFoundProductConditionAttributeRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_heading">All of the '
            . 'following conditions must be met:<ul><li class="promo_error_item">SKU cannot be '
            . '<em>sdfsdf</em>.</li></ul></li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testFoundProductActionsSkuRule()
    {
        $this->rule = $this->ruleMother->generateFoundActionAttributeRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_heading">Error applying '
            . 'rule to a particular cart item:<ul><li class="promo_error_item">SKU must be '
            . '<em>msj000</em>.</li></ul></li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testNotFoundProductActionsSkuRule()
    {
        $this->rule = $this->ruleMother->generateNotFoundActionAttributeRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_heading">Error applying '
            . 'rule to a particular cart item:<ul><li class="promo_error_heading">All of the following '
            . 'conditions must be met:<ul><li class="promo_error_item">SKU cannot be <em>msj000</em>.</li>'
            . '</ul></li></ul></li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testConditionsAndActionsRule()
    {
        $this->rule = $this->ruleMother->generateConditionAndActionRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_heading">All of the '
            . 'following conditions must be met:<ul><li class="promo_error_item">Category must be one of '
            . '<em>Root Catalog, Default Category</em>.</li></ul></li></ul><ul '
            . 'class="promo_error_message"><li class="promo_error_heading">Error applying rule to a '
            . 'particular cart item:<ul><li class="promo_error_item">SKU must be <em>msj000</em>.</li>'
            . '</ul></li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }
}
