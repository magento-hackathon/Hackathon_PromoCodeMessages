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
     * @var Hackathon_PromoCodeMessages_Model_Validator
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
        Mage::getSingleton('core/resource')->getConnection('core_write')->beginTransaction();
        $this->ruleMother = new Hackathon_PromoCodeMessages_Model_SalesRuleMother();
        $this->validator  = Mage::getModel('hackathon_promocodemessages/validator');
    }

    protected function tearDown()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/add_additional_info_on_frontend', 0);
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 0);
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_actions', 0);
        $this->storeMock = null;
        $this->quoteMock = null;
        $this->ruleMother = null;
        $this->validator = null;
        Mage::getSingleton('core/resource')->getConnection('core_write')->rollBack();
    }

    public function testValidateWithInvalidCode()
    {
        $couponCode = 'sdfsdf';
        $this->expectException(Mage_Core_Exception::class);
        $this->expectExceptionMessage('<ul class="promo_error_message"><li class="promo_error_item">Coupon code does not exist.</li></ul>');
        $this->validator->validate($couponCode, $this->quoteMock);
    }

    public function testValidateInvalidWebsiteIds()
    {
        $this->rule = $this->ruleMother->generateRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(400);

        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is not valid for this store.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);

        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testShowStopperOnlyShowsOneMessage()
    {
        // this is a failed customer group rule - but it is not checked, because the website is already wrong

        $this->rule = $this->ruleMother->generateCustomerGroupIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(400);

        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is not valid for this store.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testValidateInvalidWebsiteIdsWithAdditionalInfo()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/add_additional_info_on_frontend', 1);
        $this->rule = $this->ruleMother->generateRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(400);

        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is not valid for this store.</li></ul>'
            . '<ul class="promo_error_additional"><li class="promo_error_item">Allowed Websites: Main Website.</li></ul>';
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
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is not valid for your Customer Group.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testInvalidCustomerGroupsWithAdditionalInfo()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/add_additional_info_on_frontend', 1);
        $this->rule = $this->ruleMother->generateCustomerGroupIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(0);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is not valid for your Customer '
            . 'Group.</li></ul><ul class="promo_error_additional"><li class="promo_error_item">Allowed Customer Groups: General.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testInactive()
    {
        $this->rule = $this->ruleMother->generateInactiveRule();
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is inactive.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testExpired()
    {
        $this->rule = $this->ruleMother->generateExpiredRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is no longer valid. It expired on '
            . 'January 1, 2010.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testNotYetActive()
    {
        $this->rule = $this->ruleMother->generateNotYetActiveRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon is not valid yet. It will be active '
            . 'on January 1, 2030.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testGloballyAlreadyUsed()
    {
        $this->rule = $this->ruleMother->generateGlobalAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon was already used.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testGloballyAlreadyUsedWithAdditional()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/add_additional_info_on_frontend', 1);
        $this->rule = $this->ruleMother->generateGlobalAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon was already used.</li></ul>'
            . '<ul class="promo_error_additional"><li class="promo_error_item">It may only be used 1 time(s).</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testCustomerAlreadyUsed()
    {
        $this->rule = $this->ruleMother->generateCustomerAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon was already used.</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testCustomerAlreadyUsedWithAdditional()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/add_additional_info_on_frontend', 1);
        $this->rule = $this->ruleMother->generateCustomerAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->expectException(Mage_Core_Exception::class);
        $exceptionMsg
            = '<ul class="promo_error_message"><li class="promo_error_item">Your coupon was already used.</li></ul>'
            . '<ul class="promo_error_additional"><li class="promo_error_item">It may only be used 1 time(s).</li></ul>';
        $this->expectExceptionMessage($exceptionMsg);
        $this->validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testAddressConditionsSubtotal()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsTotalQty()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsWeight()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsPaymentMethod()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsShippingMethod()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsPostCode()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsRegion()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsRegionId()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testAddressConditionsCountryId()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testProductConditionsCategories()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testProductConditionsSku()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testProductConditionNotFoundSku()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
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

    public function testFoundProductActionsSku()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_actions', 1);
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

    public function testNotFoundProductActionsSku()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_actions', 1);
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

    public function testConditionsAndActions()
    {
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_conditions', 1);
        Mage::app()->getStore()->setConfig('checkout/promocodemessages/include_actions', 1);
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
    }
}
