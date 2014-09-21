<?php

class Hackathon_PromoCodeMessages_Model_Validator extends Mage_Core_Model_Abstract
{
    /** @var Mage_Sales_Model_Quote */
    protected $_quote = null; 
    
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
        } catch (Exception $e) {
            Mage::logException($e);
            return;
        }
        
        // no coupon
        if (!$coupon->getId()) {
            Mage::throwException($this->_formatMessage('Code does not exist.'));
        }

        /** @var $rule Mage_SalesRule_Model_Rule */
        $rule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());
        
        $this->_validateGeneral($rule);
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
     * @return string
     */
    protected function _validateGeneral($rule)
    {
        if (!$rule->getIsActive()) {
            Mage::throwException($this->_formatMessage('Your coupon is inactive.'));
        }
        
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
        
        $fromDate = new Zend_Date($rule->getFromDate());
        if (Zend_Date::now()->isEarlier($fromDate)) {
            Mage::throwException($this->_formatMessage(
                'Your coupon is not valid yet. It will get active on %s.',
                Mage::helper('core')->formatDate($fromDate),
                ''
            ));
        }

        $toDate = new Zend_Date($rule->getToDate());
        if (Zend_Date::now()->isLater($toDate)) {
            Mage::throwException($this->_formatMessage(
                'Your coupon is not valid any more. It expired on %s.',
                Mage::helper('core')->formatDate($toDate),
                ''
            ));
        }
    }
    
    /**
     * @param Mage_SalesRule_Model_Rule $rule
     * @return string
     */
    protected function _validateConditions($rule)
    {
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote() 
    {
        return $this->_quote;
    }
}
