<?php


class Hackathon_PromoCodeMessages_Model_Validator extends Mage_Core_Model_Abstract
{

    /** @var Mage_Sales_Model_Quote */
    protected $_quote = null;

    protected $_conditions = array();


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

        $this->_validateGeneral($rule);
        $this->_validateConditions($rule);
    }


    /**
     * Tranlsate the message
     *
     * @param string $message
     * @param array $params
     * @param string $internalMessage
     * @return string
     */
    protected function _formatMessage($message, $params = array(), $internalMessage = null)
    {
        if (!is_null($internalMessage) && Mage::getStoreConfigFlag('checkout/promocodemessages/use_internal_message_on_frontend')) {
            return Mage::helper('hackathon_promocodemessages')->__($internalMessage, $params);
        }

        return Mage::helper('hackathon_promocodemessages')->__($message, $params);
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
        $conditions = $this->_getConditions($rule);
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
}
