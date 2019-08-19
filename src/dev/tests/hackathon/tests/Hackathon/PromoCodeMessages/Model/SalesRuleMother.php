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

    public static function generateRule()
    {
        return self::setupBaseRule()->save();
    }

    public static function generateInactiveRule()
    {
        return self::setupBaseRule()->setIsActive(false)->save();
    }

    public static function generateExpiredRule()
    {
        return self::setupBaseRule()->setToDate('2010-01-01')->save();
    }

    public static function generateNotYetActiveRule()
    {
        return self::setupBaseRule()->setFromDate('2030-01-01')->save();
    }

    public static function generateCustomerGroupIdRule()
    {
        return self::setupBaseRule()->setCustomerGroupIds('1')->save();
    }

    public static function generateGlobalAlreadyUsedRule()
    {
        $rule = self::setupBaseRule()->save();
        $coupon = Mage::getModel('salesrule/coupon')->load($rule->getCouponCode(), 'code');
        $coupon->setTimesUsed(1)->save();

        return $rule;
    }

    public static function generateCustomerAlreadyUsedRule()
    {
        $rule = self::setupBaseRule()->save();
        $coupon = Mage::getModel('salesrule/coupon')->load($rule->getCouponCode(), 'code');
        $coupon->setTimesUsed(1)->save();
        $couponUsage = Mage::getResourceModel('salesrule/coupon_usage');
        $customer = Mage::getModel('customer/customer')->getCollection()->getFirstItem();
        $couponUsage->updateCustomerCouponTimesUsed($customer->getId(), $coupon->getId());

        return $rule;
    }

    public static function generateAddressConditionSubtotalRule()
    {
        $rule = self::setupBaseRule();
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

    public static function generateAddressConditionTotalQtyRule()
    {
        $rule = self::setupBaseRule();
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

    public static function generateAddressConditionWeightRule()
    {
        $rule = self::setupBaseRule();
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

    public static function generateAddressConditionPaymentMethodRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateAddressConditionShippingMethodRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateAddressConditionPostCodeRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateAddressConditionRegionRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateAddressConditionRegionIdRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateAddressConditionCountryIdRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateProductConditionCategoriesRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateFoundProductConditionAttributeRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateNotFoundProductConditionAttributeRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateFoundActionAttributeRule()
    {
        $rule = self::setupBaseRule();
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

    public static function generateNotFoundActionAttributeRule()
    {
        $rule = self::setupBaseRule();

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

    public static function generateConditionAndActionRule()
    {
        $rule = self::setupBaseRule();
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
    private static function setupBaseRule()
    {
        // SalesRule Rule model
        $rule = Mage::getModel('salesrule/rule');

        // Rule data
        $rule->setName('Rule name')
            ->setDescription('Rule description')
            ->setFromDate('')
            ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
            ->setCouponCode(self::generateUniqueId(5))
            ->setUsesPerCustomer(1)
            ->setUsesPerCoupon(1)
            ->setCustomerGroupIds(self::getAllCustomerGroups())
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
            ->setWebsiteIds(self::getAllWebsites());

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
            'value' => "1",
            'new_child' => ''
        ];

        return $conditions;
    }

    private static function generateUniqueId($length = null)
    {
        $rndId = crypt(uniqid(rand(), 1));
        $rndId = strip_tags(stripslashes($rndId));
        $rndId = str_replace([".", "$"], "", $rndId);
        $rndId = strrev(str_replace("/", "", $rndId));
        if (!is_null($rndId)) {
            return strtoupper(substr($rndId, 0, $length));
        }

        return strtoupper($rndId);
    }

    /**
     * @return string
     */
    private static function getAllCustomerGroups()
    {
        $customerGroups = Mage::getModel('customer/group')->getCollection()->getAllIds();

        return implode(',', $customerGroups);
    }

    /**
     * @return string
     */
    private static function getAllWebsites()
    {
        $websites = Mage::getModel('core/website')->getCollection()->getAllIds();

        return implode(',', $websites);
    }
}
