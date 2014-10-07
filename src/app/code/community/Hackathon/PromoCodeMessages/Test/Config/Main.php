<?php

/**
 * Config Tests.
 */
class Hackathon_PromoCodeMessages_Test_Config_Main extends EcomDev_PHPUnit_Test_Case_Config
{

    public function testClassAliases()
    {
        $this->assertModelAlias('hackathon_promocodemessages/validator', 'Hackathon_PromoCodeMessages_Model_Validator');
        $this->assertModelAlias('hackathon_promocodemessages/observer', 'Hackathon_PromoCodeMessages_Model_Observer');
        $this->assertHelperAlias('hackathon_promocodemessages', 'Hackathon_PromoCodeMessages_Helper_Data');
    }


    public function testDefaultValues()
    {
        $this->assertDefaultConfigValue('checkout/promocodemessages/add_additional_info_on_frontend', 0);
        $this->assertDefaultConfigValue('checkout/promocodemessages/include_conditions', 0);
    }

    public function testDepends() {
        $this->assertModuleDepends('Mage_Checkout');
        $this->assertModuleDepends('Mage_SalesRule');
    }


    public function testObserverDefinition()
    {
        $this->assertEventObserverDefined(
            'frontend',
            'sales_quote_collect_totals_after',
            'hackathon_promocodemessages/observer',
            'validateCode',
            'hackathon_promocodemessages');
    }
}
