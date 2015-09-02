<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension
 * to newer versions in the future.
 *
 * @category   Hackathon
 * @package    Hackathon_PromoCodeMessages
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Validates promo codes after the core Magento validation occurs.
 */
class Hackathon_PromoCodeMessages_Model_Validator extends Mage_Core_Model_Abstract
{

    /** @var Mage_Sales_Model_Quote */
    protected $_quote = null;

    /**
     * Array of conditions attached to the current rule.
     *
     * @var array
     */
    protected $_conditions = array();


    /**
     * Default values for possible operator options.
     *
     * @var array
     */
    protected $_operators = null;


    /**
     * Rule-specific attributes that use price. Note that product attribute type is determined dynamically.
     *
     * @var array
     */
    protected $_currency_attributes = array(
        'base_subtotal',
        'base_row_total',
        'quote_item_price',
        'quote_item_row_total',
    );

    protected $_helper;


    /**
     * Setup helper.
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('hackathon_promocodemessages');
    }


    /**
     * Main entry point.
     *
     * @param $couponCode
     * @param Mage_Sales_Model_Quote $quote
     * @return string
     */
    public function validate($couponCode, $quote)
    {
        $this->_quote = $quote;

        try {
            /** @var Mage_SalesRule_Model_Coupon $coupon */
            $coupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
        }
        catch (Exception $e) {
            Mage::logException($e);

            return;
        }

        // no coupon
        if (!$coupon->getId()) {
            Mage::throwException($this->_formatMessage('Coupon code does not exist.'));
        }

        /** @var $rule Mage_SalesRule_Model_Rule */
        $rule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());

        $this->_validateGeneral($rule, $coupon);
        if (Mage::getStoreConfigFlag('checkout/promocodemessages/include_conditions')) {
            $this->_validateConditions($rule);
        }
    }


    /**
     * Validates conditions in the "Rule Information" tab of sales rule admin.
     *
     * @param Mage_SalesRule_Model_Rule $rule
     * @param Mage_SalesRule_Model_Coupon $coupon
     * @return string
     */
    protected function _validateGeneral($rule, $coupon)
    {
        if (!$rule->getIsActive()) {
            Mage::throwException($this->_formatMessage('Your coupon is inactive.'));
        }

        // check websites
        $websiteIds = $rule->getWebsiteIds();
        if (!in_array($this->_getQuote()->getStore()->getWebsiteId(), $websiteIds)) {
            $websiteNames = Mage::getResourceModel('core/website_collection')
                ->addFieldToFilter('website_id', array('in' => $websiteIds))
                ->getColumnValues('name');
            Mage::throwException($this->_formatMessage(
                'Your coupon is not valid for this store.',
                implode(', ', $websiteNames),
                'Allowed Websites: %s.'
            ));
        }

        // check customer groups
        $groupIds = $rule->getCustomerGroupIds();
        if (!in_array($this->_getQuote()->getCustomerGroupId(), $groupIds)) {
            $customerGroupNames = Mage::getResourceModel('customer/group_collection')
                ->addFieldToFilter('customer_group_id', array('in' => $groupIds))
                ->getColumnValues('customer_group_code');
            Mage::throwException($this->_formatMessage(
                'Your coupon is not valid for your Customer Group.',
                implode(', ', $customerGroupNames),
                'Allowed Customer Groups: %s.'
            ));
        }

        // check dates
        $now = new Zend_Date(Mage::getModel('core/date')->timestamp(time()), Zend_Date::TIMESTAMP);

        // check from date
        if ($rule->getFromDate()) {
            $fromDate = new Zend_Date($rule->getFromDate(), Varien_Date::DATE_INTERNAL_FORMAT);
            if ($now->isEarlier($fromDate)) {
                Mage::throwException($this->_formatMessage(
                    'Your coupon is not valid yet. It will be active on %s.',
                    Mage::helper('core')->formatDate($fromDate, Mage_Core_Model_Locale::FORMAT_TYPE_LONG),
                    ''
                ));
            }
        }

        // check to date
        if ($rule->getToDate()) {
            $toDate = new Zend_Date($rule->getToDate(), Varien_Date::DATE_INTERNAL_FORMAT);
            if ($now->isLater($toDate)) {
                Mage::throwException($this->_formatMessage(
                    'Your coupon is no longer valid. It expired on %s.',
                    Mage::helper('core')->formatDate($toDate, Mage_Core_Model_Locale::FORMAT_TYPE_LONG),
                    ''
                ));
            }
        }

        // magemail coupon-level auto-expiration date
        $isCouponAlreadyUsed = $coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit();
        if ($coupon->getdata('magemail_expired_at') && $isCouponAlreadyUsed) {
            $expirationDate = Mage::getSingleton('core/date')->date('M d, Y', $coupon->getdata('magemail_expired_at'));
            Mage::throwException($this->_formatMessage(
                'Your coupon is no longer valid. It expired on %s.',
                $expirationDate
            ));
        }

        // check global usage limit
        if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
            Mage::throwException($this->_formatMessage(
                'Your coupon was already used.',
                $coupon->getUsageLimit(),
                sprintf('It may only be used %d time(s).', $coupon->getUsageLimit())
            ));
        }
        // check per customer usage limit
        $customerId = $this->_getQuote()->getCustomerId();
        if ($customerId && $coupon->getUsagePerCustomer()) {
            $couponUsage = new Varien_Object();
            Mage::getResourceModel('salesrule/coupon_usage')->loadByCustomerCoupon(
                $couponUsage, $customerId, $coupon->getId());
            if ($couponUsage->getCouponId() &&
                $couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()
            ) {
                Mage::throwException($this->_formatMessage(
                    'You have already used your coupon.',
                    $coupon->getUsageLimit(),
                    sprintf('It may only be used %d time(s).', $coupon->getUsagePerCustomer())
                ));
            }
        }

        // check per rule usage limit
        $ruleId = $rule->getId();
        if ($ruleId && $rule->getUsesPerCustomer()) {
            $ruleCustomer   = Mage::getModel('salesrule/rule_customer');
            $ruleCustomer->loadByCustomerRule($customerId, $ruleId);
            if ($ruleCustomer->getId()) {
                if ($ruleCustomer->getTimesUsed() >= $rule->getUsesPerCustomer()) {
                    Mage::throwException($this->_formatMessage(
                        'You have already used your coupon.',
                        $coupon->getUsageLimit(),
                        sprintf('It may only be used %d time(s).', $coupon->getUsagePerCustomer())
                    ));
                }
            }
        }
    }


    /**
     * Validate conditions in the "Conditions" tab of sales rule admin.
     *
     * @param Mage_SalesRule_Model_Rule $rule
     * @return string
     */
    protected function _validateConditions($rule)
    {
        $conditions = $this->_getConditions($rule);
        $msgs = array();

        foreach ($conditions as $condition) {
            $msgs = array_merge($msgs, $this->_processCondition($condition));
        }
        if (count($msgs) > 0) {
            $errorMsgs = $this->_multiImplode('', $msgs);
            Mage::throwException($this->_formatMessage($errorMsgs));
        }
    }


    /**
     * Process a single condition. The given condition may have subconditions, so function is recursive until it's
     * complete.
     *
     * @param array $condition
     * @return array
     */
    protected function _processCondition($condition = array())
    {
        $msgs = array();
        $msg = $this->_processRule($condition);
        if (!is_null($msg)) {
            $msgs[] = $msg;
        }

        // aggregate conditions
        if (isset($condition['aggregator']) && isset($condition['conditions'])) {
            $headingMsg = sprintf('<li class="promo_error_heading">%s<ul>',
                $this->_createAggregatedHeading($condition['aggregator']));
            $msgs[] = $headingMsg;
            $subMsgs = array();
            $subConditions = $condition['conditions'];
            foreach ($subConditions as $subCondition) {
                $subMsgs[] = $this->_processCondition($subCondition);
            }
            $msgs[] = $subMsgs;
            $msgs[] = '</ul></li>';
        }

        return $msgs;
    }


    /**
     * Gets details from the given rule condition and matches against operator.
     *
     * TODO: cleanup a bit
     *
     * @param array $condition
     * @return String containing error message
     * @throws Mage_Core_Exception
     */
    protected function _processRule($condition = array())
    {
        $attribute = $condition['attribute'];
        if (is_null($attribute)) { // product
            return null;
        }
        $operator = $condition['operator'];
        $value = $condition['value'];
        $type = $condition['type'];
        $ruleType = Mage::getModel($type);
        $isCurrency = in_array($attribute, $this->_currency_attributes);
        $msg = null;

        // categories
        if ($attribute == 'category_ids') {
            $categoryIds = explode(',', $value);

            // get collection and filter by cat ids
            $catCollection = Mage::getModel('catalog/category')->getCollection();
            $catCollection->addAttributeToFilter('entity_id', array('in' => $categoryIds));
            $catCollection->addAttributeToSelect('name');

            $categories = $catCollection->load()->getColumnValues('name');
            $value = implode(', ', $categories);
        }

        // product attributes
        if ($type == 'salesrule/rule_condition_product') {
            $attributeModel = Mage::getModel('eav/entity_attribute')
                ->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attribute);
            $storeId = Mage::app()->getStore()->getStoreId();

            // determine if we should format currency
            if ($attributeModel->getBackendModel() == 'catalog/product_attribute_backend_price') {
                $isCurrency = true;
            }

            // attribute may use a source model
            if ($attributeModel->usesSource()) {
                $attributeId = $attributeModel->getAttributeId();
                $collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                    ->setAttributeFilter($attributeId)
                    ->setStoreFilter($storeId, false)
                    ->addFieldToFilter('tsv.option_id', array('in' => $value));
                $collection
                    ->getSelect()
                    ->limit(1);

                if ($collection->getSize()) {
                    $value = $collection->getFirstItem()->getValue();
                }
            }
        }

        // Loop through operators looking for a match
        foreach ($this->_getOperators() as $operatorCode => $operatorText) {
            if ($operatorCode == $operator) {
                // get the rule attributes and their values
                $attributeOptions = $ruleType->getAttributeOption();
                foreach ($attributeOptions as $attributeOptionCode => $attributeOptionText) {
                    if ($attribute == $attributeOptionCode) {
                        $value = $isCurrency ? Mage::helper('core')->currency($value, true, false) : $value;
                        $msg = sprintf('%s %s <em>%s</em>.', $attributeOptionText, $operatorText, $value);
                        break;
                    }
                }
                break;
            }
        }

        return $msg ? '<li class="promo_error_item">' . $msg . '</li>' : null;
    }


    /**
     * Setup a heading for aggregated rule conditions.
     *
     * @param String $aggregator "any" or "all"
     * @return String containing aggregate heading
     */
    protected function _createAggregatedHeading($aggregator)
    {
        if ($aggregator == 'any') {
            $heading = sprintf('%s',
                $this->_helper->__('At least one of the following conditions must be met:'));
        }
        else {
            $heading = sprintf('%s',
                $this->_helper->__('All of the following conditions must be met:'));
        }

        return $heading;
    }


    /**
     * Tranlsate the message.
     *
     * @param string $message
     * @param string $params
     * @param string $internalMessage
     * @return string containing entire error message
     */
    protected function _formatMessage($message, $params = '', $internalMessage = null)
    {
        $message = sprintf('<ul class="promo_error_message">%s</ul>',
            $this->_helper->__($message, $params));

        if (!is_null($internalMessage) &&
            Mage::getStoreConfigFlag('checkout/promocodemessages/add_additional_info_on_frontend')
        ) {
            $message .= sprintf('<ul class="promo_error_additional">%s</ul>',
                $this->_helper->__($internalMessage, $params));
        }

        return $message;
    }


    /**
     * Implode a multidimensional array.
     *
     * @param $glue
     * @param $array
     * @return string
     */
    protected function _multiImplode($glue, $array)
    {
        $ret = '';

        foreach ($array as $item) {
            if (is_array($item)) {
                $ret .= $this->_multiImplode($glue, $item) . $glue;
            }
            else {
                $ret .= $item . $glue;
            }
        }

        return $ret;
    }


    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_quote;
    }


    /**
     * Unserialize the conditions from the rule.
     *
     * @param Mage_SalesRule_Model_Rule $rule
     * @return Array of rule conditions
     */
    protected function _getConditions($rule)
    {
        if (count($this->_conditions) == 0) {
            if ($rule->getId()) {
                $data = unserialize($rule->getData('conditions_serialized'));
                if (isset($data['conditions'])) {
                    $this->_conditions = $data['conditions'];
                }
            }
        }

        return $this->_conditions;
    }


    /**
     * Default operator options getter
     * Provides all possible operator options
     *
     * @return array
     */
    protected function _getOperators()
    {
        if (null === $this->_operators) {
            $this->_operators = array(
                '==' => $this->_helper->__('must be'),
                '!=' => $this->_helper->__('must not be'),
                '>=' => $this->_helper->__('must be equal or greater than'),
                '<=' => $this->_helper->__('must be  equal or less than'),
                '>' => $this->_helper->__('must be greater than'),
                '<' => $this->_helper->__('must be less than'),
                '{}' => $this->_helper->__('must contain'),
                '!{}' => $this->_helper->__('must not contain'),
                '()' => $this->_helper->__('must be one of'),
                '!()' => $this->_helper->__('must not be one of')
            );
        }

        return $this->_operators;
    }
}
