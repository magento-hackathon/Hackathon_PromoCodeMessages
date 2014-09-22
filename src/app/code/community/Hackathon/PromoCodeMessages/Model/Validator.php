<?php


class Hackathon_PromoCodeMessages_Model_Validator extends Mage_Core_Model_Abstract
{

    /** @var Mage_Sales_Model_Quote */
    protected $_quote = null;

    protected $_conditions = array();


    /**
     * Default values for possible operator options
     * @var array
     */
    protected $_defaultOperatorOptions = null;
    


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
        $this->_validateConditions($rule);
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
        if (!is_null($internalMessage) && Mage::getStoreConfigFlag('checkout/promocodemessages/add_additional_info_on_frontend')) {
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
                'Your coupon is not valid for your Customer Group',
                implode(', ', $customerGroupNames),
                'Allowed Customer Groups: %s.'
            ));
        }

        // check from date
        $fromDate = new Zend_Date($rule->getFromDate());
        if (Zend_Date::now()->isEarlier($fromDate)) {
            Mage::throwException($this->_formatMessage(
                'Your coupon is not valid yet. It will get active on %s.',
                Mage::helper('core')->formatDate($fromDate),
                ''
            ));
        }

        // check to date
        $toDate = new Zend_Date($rule->getToDate());
        if (Zend_Date::now()->isLater($toDate)) {
            Mage::throwException($this->_formatMessage(
                'Your coupon is not valid any more. It expired on %s.',
                Mage::helper('core')->formatDate($toDate),
                ''
            ));
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
     * @param Mage_SalesRule_Model_Rule $rule
     * @return string
     */
    protected function _validateConditions($rule)
    {
        $conditions = $this->_getConditions($rule);
        foreach ($conditions as $condition)
        {
            $attribute = $condition['attribute'];
            $operator = $condition['operator'];
            $value = $condition['value'];

            foreach ($this->getDefaultOperatorOptions() as $op => $text)
            {
                if ($op == $operator) {
                    $msg = sprintf('%s must be %s %s', $attribute, $text, $value);
                    Mage::throwException($this->_formatMessage(
                        $msg,
                        array($attribute, $text, $value),
                        ''
                    ));
                }
            }
        }
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
    public function getDefaultOperatorOptions()
    {
        $_helper = Mage::helper('rule');
        if (null === $this->_defaultOperatorOptions) {
            $this->_defaultOperatorOptions = array(
                '=='  => $_helper->__('is'),
                '!='  => $_helper->__('is not'),
                '>='  => $_helper->__('equals or greater than'),
                '<='  => $_helper->__('equals or less than'),
                '>'   => $_helper->__('greater than'),
                '<'   => $_helper->__('less than'),
                '{}'  => $_helper->__('contains'),
                '!{}' => $_helper->__('does not contain'),
                '()'  => $_helper->__('is one of'),
                '!()' => $_helper->__('is not one of')
            );
        }
        return $this->_defaultOperatorOptions;
    }
}
