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

class Hackathon_PromoCodeMessages_Model_Observer
{

    /**
     * Called on sales_quote_collect_totals_after. Ensure that the action is couponPost before continuing.
     *
     * @param Varien_Event_Observer $observer
     */
    public function validateCode(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag('checkout/promocodemessages/enabled')) {
            $action = Mage::app()->getRequest()->getActionName();

            if ($action == 'couponPost') {

                if (Mage::app()->getRequest()->getParam('remove') == 1) {
                    return;
                }

                $quote = $observer->getQuote();
                $couponCode = $quote->getCouponCode();

                if (!$couponCode || $couponCode == '') {
                    // parent validation has failed
                    $couponCode = (string)Mage::app()->getRequest()->getParam('coupon_code');
                    Mage::getModel('hackathon_promocodemessages/validator')->validate($couponCode, $quote);
                }
            }
        }
    }
}
