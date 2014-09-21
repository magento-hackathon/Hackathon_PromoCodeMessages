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
            $coupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
            // no coupon
            if (!$coupon->getId())
            {
                return $this->_formatMessage('Code does not exist.');
            }

            $rule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());
            if (!$rule->getIsActive()) {
                return $this->_formatMessage('Code is inactive');
            }
        }

        catch (Exception $e)
        {
            Mage::logException($e);
            Mage::throwException('exception'); // TODO: put more descriptive message here
        }
        return "invalid";
    }


    protected function _formatMessage($msg)
    {
        return Mage::helper('hackathon_promocodemessage')->__($msg);
    }
}
