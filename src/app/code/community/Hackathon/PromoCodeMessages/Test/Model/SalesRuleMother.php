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

/**
 * Used to generate sales rules for integration tests. Do not run in production.
 */
class Hackathon_PromoCodeMessages_Model_SalesRuleMother
{

    /**
     * @var Mage_SalesRule_Model_Rule
     */
    private $rule;

    public function __construct()
    {
        $this->rule = $this->setupBaseRule();
    }

    public function generateRule()
    {
        return $this->rule->save();
    }

    public function generateInactiveRule()
    {
        return $this->rule->setIsActive(false)->save();
    }

    public function generateExpiredRule()
    {
        return $this->rule->setToDate('2010-01-01')->save();
    }

    public function generateNotYetActiveRule()
    {
        return $this->rule->setFromDate('2030-01-01')->save();
    }

    public function generateCustomerGroupIdRule()
    {
        return $this->rule->setCustomerGroupIds('1')->save();
    }

    public function generateGlobalAlreadyUsedRule()
    {
        $rule = $this->rule->save();
        $coupon = Mage::getModel('salesrule/coupon')->load($rule->getCouponCode(), 'code');
        $coupon->setTimesUsed(1)->save();

        return $rule;
    }

    public function generateCustomerAlreadyUsedRule()
    {
        $rule = $this->rule->save();
        $coupon = Mage::getModel('salesrule/coupon')->load($rule->getCouponCode(), 'code');
        $coupon->setTimesUsed(1)->save();
        $couponUsage = Mage::getResourceModel('salesrule/coupon_usage');
        $customer = Mage::getModel('customer/customer')->getCollection()->getFirstItem();
        $couponUsage->updateCustomerCouponTimesUsed($customer->getId(), $coupon->getId());

        return $rule;
    }

    public function generateAddressConditionSubtotalRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;
        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'base_subtotal',
                'operator' => '>=',
                'value' => 1000
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionTotalQtyRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;
        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'total_qty',
                'operator' => '==',
                'value' => 5
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionWeightRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;
        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'weight',
                'operator' => '>',
                'value' => '5 lbs'
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionPaymentMethodRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'payment_method',
                'operator' => '==',
                'value' => 'checkmo'
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionShippingMethodRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'shipping_method',
                'operator' => '==',
                'value' => 'flatrate_flatrate'
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionPostCodeRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'postcode',
                'operator' => '()',
                'value' => '11215, 12346'
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionRegionRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'region',
                'operator' => '==',
                'value' => 'Quebec'
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionRegionIdRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'region_id',
                'operator' => '==',
                'value' => '1'
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateAddressConditionCountryIdRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_address',
                'attribute' => 'country_id',
                'operator' => '==',
                'value' => 'US'
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateProductConditionCategoriesRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $categoryIds = Mage::getModel('catalog/category')->getCollection()->getAllIds(2);

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                'value' => 1,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $conditions['1--1--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'category_ids',
                'operator' => '()',
                'value' => implode(',', $categoryIds),
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateFoundProductConditionAttributeRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                'value' => 1,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $conditions['1--1--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'sku',
                'operator' => '==',
                'value' => 'msj000',
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateNotFoundProductConditionAttributeRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                'value' => 0,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $conditions['1--1--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'sku',
                'operator' => '==',
                'value' => 'sdfsdf',
            ];
        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateFoundActionAttributeRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;
        $actions = [];
        $actions['1'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                'value' => 1,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $actions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'sku',
                'operator' => '==',
                'value' => 'msj000',
            ];
        $rule->setData('actions', $actions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateNotFoundActionAttributeRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;

        $actions = self::generateRuleConditionCombineArray();
        $actions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                'value' => 0,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $actions['1--1--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'sku',
                'operator' => '==',
                'value' => 'msj000',
            ];
        $rule->setData('actions', $actions);
        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    public function generateConditionAndActionRule(): \Mage_SalesRule_Model_Rule
    {
        $rule = $this->rule;
        $categoryIds = Mage::getModel('catalog/category')->getCollection()->getAllIds(2);

        $conditions = self::generateRuleConditionCombineArray();
        $conditions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                'value' => 1,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $conditions['1--1--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'category_ids',
                'operator' => '()',
                'value' => implode(',', $categoryIds),
            ];
        $rule->setData('conditions', $conditions);

        $actions = [];
        $actions['1'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                'value' => 1,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $actions['1--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'sku',
                'operator' => '==',
                'value' => 'msj000',
            ];
        $rule->setData('actions', $actions);

        $rule->loadPost($rule->getData())->save();

        return $rule;
    }

    /**
     * Sets up a base rule with 10% off. Used for generating various rule types.
     *
     * @return Mage_SalesRule_Model_Rule
     */
    private function setupBaseRule(): \Mage_SalesRule_Model_Rule
    {
        // SalesRule Rule model
        $rule = Mage::getModel('salesrule/rule');

        // Rule data
        $rule->setName('Rule name')
            ->setDescription('Rule description')
            ->setFromDate('')
            ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
            ->setCouponCode($this->generateUniqueId(5))
            ->setUsesPerCustomer(1)
            ->setUsesPerCoupon(1)
            ->setCustomerGroupIds($this->getAllCustomerGroups())
            ->setIsActive(1)
            ->setConditionsSerialized('')
            ->setActionsSerialized('')
            ->setStopRulesProcessing(0)
            ->setIsAdvanced(1)
            ->setProductIds('')
            ->setSortOrder(0)
            ->setSimpleAction(Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION)
            ->setDiscountAmount(10)
            ->setDiscountQty(1)
            ->setDiscountStep(0)
            ->setSimpleFreeShipping('0')
            ->setApplyToShipping('0')
            ->setIsRss(0)
            ->setWebsiteIds($this->getAllWebsites());

        return $rule;
    }

    /**
     * @return array
     */
    private static function generateRuleConditionCombineArray(): array
    {
        $conditions = [];
        $conditions[1] = [
            'type' => 'salesrule/rule_condition_combine',
            'aggregator' => 'all',
            'value' => '1',
            'new_child' => ''
        ];

        return $conditions;
    }

    private function generateUniqueId($length = null): string
    {
        $rndId = crypt(uniqid(mt_rand(), 1));
        $rndId = strip_tags(stripslashes($rndId));
        $rndId = str_replace(['.', '$'], '', $rndId);
        $rndId = strrev(str_replace('/', '', $rndId));
        if ($rndId !== null) {
            return strtoupper(substr($rndId, 0, $length));
        }

        return strtoupper($rndId);
    }

    /**
     * @return string
     */
    private function getAllCustomerGroups(): string
    {
        $customerGroups = Mage::getModel('customer/group')->getCollection()->getAllIds();

        return implode(',', $customerGroups);
    }

    /**
     * @return string
     */
    private function getAllWebsites(): string
    {
        $websites = Mage::getModel('core/website')->getCollection()->getAllIds();

        return implode(',', $websites);
    }
}
