<?php

class Hackathon_PromoCodeMessages_Model_Validator extends Mage_Core_Model_Abstract
{

    /**
     * @param $couponCode
     * @return string
     */
    public function validate($couponCode)
    {

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
     * @return string
     */
    protected function _formatMessage($message)
    {
        return Mage::helper('hackathon_promocodemessages')->__($message);
    }

    /**
     * @param Mage_SalesRule_Model_Rule $rule
     * @return string
     */
    protected function _validateGeneral($rule)
    {
        if (!$rule->getIsActive()) {
            Mage::throwException($this->_formatMessage('Code is inactive'));
        }
    }
    
    /**
     * @param Mage_SalesRule_Model_Rule $rule
     * @return string
     */
    protected function _validateConditions($rule)
    {
    }
}
