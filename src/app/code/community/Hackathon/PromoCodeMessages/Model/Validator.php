<?php


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
            Mage::throwException($this->_formatMessage('Code does not exist.'));
        }

        /** @var $rule Mage_SalesRule_Model_Rule */
        $rule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());

        $this->_validateGeneral($rule, $coupon);
        if (Mage::getStoreConfigFlag('checkout/promocodemessages/include_conditions')) {
            $this->_validateConditions($rule);
        }
    }


    /**
     * Tranlsate the message
     *
     * @param string $message
     * @param string $params
     * @param string $internalMessage
     * @return string
     */
    protected function _formatMessage($message, $params = '', $internalMessage = null)
    {
        $message = Mage::helper('hackathon_promocodemessages')->__($message, $params);
        if (!is_null($internalMessage) &&
            Mage::getStoreConfigFlag('checkout/promocodemessages/add_additional_info_on_frontend')
        ) {
            $message .= '<br />' . Mage::helper('hackathon_promocodemessages')->__($internalMessage, $params);
        }

        return $message;
    }


    /**
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

        // check from date
        if ($rule->getFromDate()) {
            $fromDate = new Zend_Date($rule->getFromDate(), Varien_Date::DATE_INTERNAL_FORMAT);
            if (Zend_Date::now()->isEarlier($fromDate)) {
                Mage::throwException($this->_formatMessage(
                    'Your coupon is not valid yet. It will be active on %s.',
                    Mage::helper('core')->formatDate($fromDate),
                    ''
                ));
            }
        }

        // check to date
        if ($rule->getToDate()) {
            $toDate = new Zend_Date($rule->getToDate(), Varien_Date::DATE_INTERNAL_FORMAT);
            if (Zend_Date::now()->isLater($toDate)) {
                Mage::throwException($this->_formatMessage(
                    'Your coupon is no longer valid. It expired on %s.',
                    Mage::helper('core')->formatDate($toDate),
                    ''
                ));
            }
        }

        // check global usage limit
        if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
            Mage::throwException($this->_formatMessage(
                'Your coupon was already used.',
                $coupon->getUsageLimit(),
                'It may only be used %s time(s).'
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
                    $coupon->getUsagePerCustomer(),
                    'It may only be used %s time(s) per customer.'
                ));
            }
        }
    }


    /**
     * Validate conditions.
     * TODO: format currency
     *
     * @param Mage_SalesRule_Model_Rule $rule
     * @return string
     */
    protected function _validateConditions($rule)
    {
        $conditions = $this->_getConditions($rule);
        $msgs = array();

        foreach ($conditions as $condition) {
            $type = $condition['type'];

            // Get rule type in order to determine attributes
            if ($type == 'salesrule/rule_condition_address') {
                $msgs = array_merge($msgs, $this->_processRuleTypes($condition));
            }
            elseif ($type == 'salesrule/rule_condition_product_found') {
                // this rule type has subconditions
                $msgs[] = $this->_createAggregatedHeading($condition['aggregator']);
                $subConditions = $condition['conditions'];
                foreach ($subConditions as $subCondition) {
                    $msgs = array_merge($msgs, $this->_processRuleTypes($subCondition));
                }
            }
            elseif ($type == 'salesrule/rule_condition_product_subselect') {
                // this rule type has a condition AND subconditions
                $msgs[] = $this->_createAggregatedHeading($condition['aggregator']);
                $msgs = array_merge($msgs, $this->_processRuleTypes($condition));
                $subConditions = $condition['conditions'];
                foreach ($subConditions as $subCondition) {
                    $msgs = array_merge($msgs, $this->_processRuleTypes($subCondition));
                }
            }
        }
        if (count($msgs) > 0) {
            $errorMsgs = implode('<br/>', $msgs);
            Mage::throwException($this->_formatMessage($errorMsgs));
        }
    }


    /**
     * Setup a heading for aggregated rule conditions.
     * @param String $aggregator "any" or "all"
     * @return mixed
     */
    protected function _createAggregatedHeading($aggregator) {

        $heading = Mage::helper('hackathon_promocodemessages')->__('All of the following conditions must be met:<br/>');
        if ($aggregator == 'any') {
            $heading = Mage::helper('hackathon_promocodemessages')->__('At least one of the following conditions must be met:<br/>');
        }
        return $heading;
    }


    /**
     * Gets details from the given rule condition and matches against operator.
     *
     * @param array $condition
     * @throws Mage_Core_Exception
     */
    protected function _processRuleTypes($condition = array())
    {
        $attribute = $condition['attribute']; // TODO: handle "category_ids"
        $operator = $condition['operator'];
        $value = $condition['value'];
        $type = $condition['type'];
        $ruleType = Mage::getModel($type);
        $msgs = array();

        if ($attribute == 'category_ids') {
            $categoryIds = explode(',', $value);
            $values = array();
            foreach ($categoryIds as $categoryId) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $values[] = $category->getName();
            }
            $value = implode(', ', $values);
        }
        if ($type == 'salesrule/rule_condition_product') {
            // load attribute; it may have a source model where we'll need the store display value
            $attributeModel = Mage::getModel('eav/entity_attribute')
                ->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attribute);

            if ($attributeModel->getSourceModel()) {

                $storeId = Mage::app()->getStore()->getStoreId();
                $attributeId = $attributeModel->getAttributeId();
                $collection = Mage::getResourceModel('eav/entity_attribute_option_collection') // TODO: better way?
                     ->setAttributeFilter($attributeId)
                     ->setStoreFilter($storeId, false)
                     ->addFieldToFilter('tsv.option_id', array('in' => $value));
                if ($collection->getSize() > 0) {
                    $item = $collection->getFirstItem();
                    $value = $item->getValue();
                }
            }
        }

        foreach ($this->_getOperators() as $operatorCode => $operatorText) {
            if ($operatorCode == $operator) {
                $attributeOptions = $ruleType->getAttributeOption();
                foreach ($attributeOptions as $attributeOptionCode => $attributeOptionText) {
                    if ($attribute == $attributeOptionCode) {
                        $msg = sprintf('%s %s <em>%s</em>.', $attributeOptionText, $operatorText, $value);
                        $msgs[] = $msg;
                        break;
                    }
                }
                break;
            }
        }

        return $msgs;
    }


    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_quote;
    }


    /**
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
        $_helper = Mage::helper('rule');
        if (null === $this->_operators) {
            $this->_operators = array(
                '==' => $_helper->__('must be'),
                '!=' => $_helper->__('must not be'),
                '>=' => $_helper->__('must be equal or greater than'),
                '<=' => $_helper->__('must be  equal or less than'),
                '>' => $_helper->__('must be greater than'),
                '<' => $_helper->__('must be less than'),
                '{}' => $_helper->__('must contain'),
                '!{}' => $_helper->__('must not contain'),
                '()' => $_helper->__('must be one of'),
                '!()' => $_helper->__('must not be one of')
            );
        }

        return $this->_operators;
    }
}
