<?php

class Hackathon_PromoCodeMessages_Model_Observer
{

    /**
     * Called on sales_quote_collect_totals_after. Ensure that the action is couponPost before continuing.
     * @param Varien_Event_Observer $observer
     */
    public function validateCode(Varien_Event_Observer $observer)
    {
        $action = Mage::app()->getRequest()->getActionName();
        if ($action == 'couponPost')
        {
            Mage::log('pepe');
        }
    }
}
