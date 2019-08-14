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

class Hackathon_PromoCodeMessages_Model_ValidatorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var string
     */
    private $couponCode;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Mage_Sales_Model_Quote
     */
    private $quoteMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Mage_Core_Model_Store
     */
    private $storeMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Mage_SalesRule_Model_Coupon
     */
    private $couponMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Mage_SalesRule_Model_Rule
     */
    private $ruleMock;

    /**
     * @var Mage_SalesRule_Model_Rule
     */
    private $rule;

    protected function setUp()
    {
        Mage::app('default');
        $this->couponCode = 'actions';
        $this->quoteMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getCustomerGroupId', 'getCustomerId'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Mage_Core_Model_Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();
        //        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);

        $this->couponMock = $this->getMockBuilder(Mage_SalesRule_Model_Coupon::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getId', 'getRuleId', 'getCouponId',])
            ->getMock();
        //        $this->couponMock->expects($this->once())->method('getRuleId')->willReturn(1);
        //        $this->couponMock->expects($this->once())->method('getCouponId')->willReturn(1);

        $this->ruleMock = $this->getMockBuilder(Mage_SalesRule_Model_Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getRuleId',
                    'getName',
                    'getDescription',
                    'getFromDate',
                    'getToDate',
                    'getSimpleAction',
                    'getIsActive',
                    'getCustomerGroupIds',
                    'getWebsiteIds'
                ]
            )
            ->getMock();
    }

    protected function tearDown()
    {
        $this->storeMock = null;
        $this->quoteMock = null;
        $this->couponMock = null;
        $this->ruleMock = null;
        if ($this->rule) {
            $this->rule->delete();
        }
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Coupon code does not exist.</ul>
     */
    public function testValidateWithInvalidCode()
    {
        $couponCode = 'sdfsdf';
        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($couponCode, $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Your coupon is not valid for this store.</ul><ul class="promo_error_additional">Allowed Websites: Main Website.</ul>
     */
    public function testValidateInvalidWebsiteIds()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(400);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Your coupon is not valid for your Customer Group.</ul><ul class="promo_error_additional">Allowed Customer Groups: General.</ul>
     */
    public function testInvalidCustomerGroups()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateCustomerGroupIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(0);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Your coupon is inactive.</ul>
     */
    public function testInactiveRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateInactiveRule();

        /** @var Hackathon_PromoCodeMessages_Model_Validator $validator */
        $validator = new Hackathon_PromoCodeMessages_Model_Validator();

        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Your coupon is no longer valid. It expired on January 1, 2010.</ul><ul class="promo_error_additional"></ul>
     */
    public function testExpiredRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateExpiredRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Your coupon is not valid yet. It will be active on January 1, 2030.</ul><ul class="promo_error_additional"></ul>
     */
    public function testNotYetActiveRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateNotYetActiveRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    public function testMageMailExpiredRule()
    {
        $this->markTestIncomplete('need to fix magemail_expired_at');
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateMageMailExpireRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Your coupon was already used.</ul><ul class="promo_error_additional">It may only be used 1 time(s).</ul>
     */
    public function testGloballyAlreadyUsedRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateGlobalAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message">Your coupon was already used.</ul><ul class="promo_error_additional">It may only be used 1 time(s).</ul>
     */
    public function testCustomerAlreadyUsedRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateCustomerAlreadyUsedRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Subtotal must be equal or greater than <em>$1,000.00</em>.</li></ul>
     */
    public function testAddressConditionsSubtotalRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionSubtotalRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Total Items Quantity must be <em>5</em>.</li></ul>
     */
    public function testAddressConditionsTotalQtyRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionTotalQtyRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Total Weight must be greater than <em>5 lbs</em>.</li></ul>
     */
    public function testAddressConditionsWeightRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionWeightRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Payment Method must be <em>Check / Money order</em>.</li></ul>
     */
    public function testAddressConditionsPaymentMethodRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionPaymentMethodRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Shipping Method must be <em>flatrate_flatrate</em>.</li></ul>
     */
    public function testAddressConditionsShippingMethodRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionShippingMethodRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Shipping Postcode must be one of <em>11215, 12346</em>.</li></ul>
     */
    public function testAddressConditionsPostCodeRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionPostCodeRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Shipping Region must be <em>Quebec</em>.</li></ul>
     */
    public function testAddressConditionsRegionRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionRegionRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Shipping State/Province must be <em>Alabama</em>.</li></ul>
     */
    public function testAddressConditionsRegionIdRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionRegionIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * @throws Mage_Core_Exception
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage <ul class="promo_error_message"><li class="promo_error_item">Shipping Country must be <em>United States</em>.</li></ul>
     */
    public function testAddressConditionsCountryIdRule()
    {
        $this->rule = Hackathon_PromoCodeMessages_Model_SalesRuleMother::generateAddressConditionCountryIdRule();
        $this->quoteMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $validator = new Hackathon_PromoCodeMessages_Model_Validator();
        $validator->validate($this->rule->getCouponCode(), $this->quoteMock);
    }

    /**
     * Allow calling protected methods. TODO: needed?
     *
     * @param $name
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getMethod($name)
    {
        $class = new ReflectionClass('Hackathon_PromoCodeMessages_Model_Validator');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

}
