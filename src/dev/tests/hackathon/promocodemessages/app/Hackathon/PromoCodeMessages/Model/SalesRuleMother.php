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
 * Used to generate sales rules for unit tests. Do not run in production.
 */
class Hackathon_PromoCodeMessages_Model_SalesRuleMother
{

    public static function generateRule()
    {
        return self::setupBaseRule()->save();
    }

    /**
     * @return false|Mage_Core_Model_Abstract|Mage_SalesRule_Model_Rule
     * @throws Exception
     */
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

    public static function generateMageMailExpireRule()
    {
        $rule = self::setupBaseRule()->save();
        $coupon = Mage::getModel('salesrule/coupon')->load($rule->getCouponCode(), 'code');
        // TODO: magemail_expired_at not persisting
        $coupon->setTimesUsed(1)->setData('magemail_expired_at', '2010-01-01')->save();

        return $rule;
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

    public static function generateProductConditionSkuRule()
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

    /**
     * @return false|Mage_Core_Model_Abstract|Mage_SalesRule_Model_Rule
     * @throws Exception
     */
    public static function generateRuleOld()
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
            ->setWebsiteIds(self::getAllWebsites())
            ->setStoreLabels(array('My Rule Frontend Label'));

        // Product found condition type
        $productFoundCondition = Mage::getModel('salesrule/rule_condition_product_found')
            ->setType('salesrule/rule_condition_product_found')
            ->setValue(1)// 0 == not found, 1 == found
            ->setAggregator('all');     // match all conditions

        // 'Attribute set id 1' product action condition
        $attributeSetCondition = Mage::getModel('salesrule/rule_condition_product')
            ->setType('salesrule/rule_condition_product')
            ->setAttribute('attribute_set_id')
            ->setOperator('==')
            ->setValue(1);

        // Bind attribute set condition to product found condition
        $productFoundCondition->addCondition($attributeSetCondition);

        // If a product with 'attribute set id 1' is found in the cart
        //$rule->getConditions()->addCondition($productFoundCondition);
        // Only apply the rule discount to this specific product
        $rule->getActions()->addCondition($attributeSetCondition);

        $rule->save();

        return $rule;
    }

    /**
     * Sets up a base rule with 10% off. Used for generating various rule types.
     *
     * @return false|Mage_Core_Model_Abstract|Mage_SalesRule_Model_Rule
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
            ->setWebsiteIds(self::getAllWebsites())
            ->setStoreLabels(array('My Rule Frontend Label'));

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

    /**
     * @throws Exception
     */
    public function generateRuleOld1()
    {
        $uniqueId = $this->generateUniqueId(10);
        $rule = Mage::getModel('salesrule/rule');
        $rule->setName($uniqueId);
        $rule->setDescription('Unit test rule');
        $rule->setFromDate(date('Y-m-d'));//starting today
        //$rule->setToDate('2011-01-01');//if you need an expiration date
        $rule->setCouponCode($uniqueId);
        $rule->setUsesPerCoupon(1);//number of allowed uses for this coupon
        $rule->setUsesPerCustomer(1);//number of allowed uses for this coupon for each customer
        $rule->setCustomerGroupIds($this->getAllCustomerGroups());//if you want only certain groups replace getAllCustomerGroups() with an array of desired ids
        $rule->setIsActive(1);
        $rule->setStopRulesProcessing(0);//set to 1 if you want all other rules after this to not be processed
        $rule->setIsRss(0);//set to 1 if you want this rule to be public in rss
        $rule->setIsAdvanced(1);//have no idea what it means :)
        $rule->setProductIds('');
        $rule->setSortOrder(0);// order in which the rules will be applied

        $rule->setSimpleAction('by_percent');
        //all available discount types
        //by_percent - Percent of product price discount
        //by_fixed - Fixed amount discount
        //cart_fixed - Fixed amount discount for whole cart
        //buy_x_get_y - Buy X get Y free (discount amount is Y)

        $rule->setDiscountAmount('20');//the discount amount/percent. if SimpleAction is by_percent this value must be <= 100
        $rule->setDiscountQty(0);//Maximum Qty Discount is Applied to
        $rule->setDiscountStep(0);//used for buy_x_get_y; This is X
        $rule->setSimpleFreeShipping(0);//set to 1 for Free shipping
        $rule->setApplyToShipping(1);//set to 0 if you don't want the rule to be applied to shipping
        $rule->setWebsiteIds($this->getAllWebsites());//if you want only certain websites replace getAllWbsites() with an array of desired ids

        $conditions = array();
        $conditions[1] = array(
            'type' => 'salesrule/rule_condition_combine',
            'aggregator' => 'all',
            'value' => "1", //[UPDATE] added quotes on the value. Thanks Aziz Rattani [/UPDATE]
            'new_child' => ''
        );
        //the conditions above are for 'if all of these conditions are true'
        //for if any one of the conditions is true set 'aggregator' to 'any'
        //for if all of the conditions are false set 'value' to 0.
        //for if any one of the conditions is false set 'aggregator' to 'any' and 'value' to 0
        $conditions['1--1'] = Array
        (
            'type' => 'salesrule/rule_condition_address',
            'attribute' => 'base_subtotal',
            'operator' => '>=',
            'value' => 200
        );
        //the constraints above are for 'Subtotal is equal or grater than 200'
        //for 'equal or less than' set 'operator' to '<='... You get the idea other
        // operators for numbers: '==', '!=', '>', '<'
        //for 'is one of' set operator to '()';
        //for 'is not one of' set operator to '!()';
        //in this example the constraint is on the subtotal
        //for other attributes you can change the value for 'attribute' to:
        // 'total_qty', 'weight', 'payment_method', 'shipping_method', 'postcode', 'region',
        // 'region_id', 'country_id'

        //to add another constraint on product attributes (not cart attributes like above)
        // uncomment and change the following:

        $conditions['1--2'] =
            [
                'type' => 'salesrule/rule_condition_product_found',
                //-> means 'if all of the following are true' - same rules as above for
                // 'aggregator' and 'value'
                // other values for type:
                // 'salesrule/rule_condition_product_subselect'
                // 'salesrule/rule_condition_combine'
                'value' => 1,
                'aggregator' => 'all',
                'new_child' => '',
            ];

        $conditions['1--2--1'] =
            [
                'type' => 'salesrule/rule_condition_product',
                'attribute' => 'sku',
                'operator' => '==',
                'value' => '12',
            ];

        //$conditions['1--2--1'] means sku equals 12.
        // For other constraints change 'attribute', 'operator'(see list above), 'value'

        $rule->setData('conditions', $conditions);
        $rule->loadPost($rule->getData());
        $labels = array();
        $labels[0] = 'Default store label';//default store label
        $labels[1] =
            'Label for store with id 1'; //add one line for each store view you have.
        // The key is the store view ID
        $rule->setStoreLabels($labels);

        $rule->setCouponType(2);
        $rule->save();
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
        //        $groups = [];
        //        foreach ($customerGroups as $group) {
        //            $groups[] = $group->getId();
        //        }

        return implode(',', $customerGroups);
    }

    /**
     * @return string
     */
    private static function getAllWebsites()
    {
        $websites = Mage::getModel('core/website')->getCollection()->getAllIds();
        //        $websiteIds = [];
        //        foreach ($websites as $website) {
        //            $websiteIds[] = $website->getId();
        //        }

        return implode(',', $websites);
    }

}
