Hackathon_PromoCodeMessages
===========================

Provide improved error messages when entering a promo code fails.

Current State
-------------
Beta. Still working through the various rule types. Currently supports conditions under *General Information* tab and 
*Conditions* tab.

Options
-------
There are two system configuration options under Sales -> Checkout -> Promo Code Error Messages. You can display 
additional information about the error (for example, if the customer is not in the correct group for the rule, you can
display the required groups). In addition, error messaging for the more complex condition-based rules can be turned off.
This part is still under active development.

Translations
------------
Translation file available for English and German (partial).


Styling
-------
Each message is wrapped in a class to use for styling:

**promo_error_message:** Wraps entire message.

**promo_error_additional:** Wraps additional information if enabled in System Config.

**promo_error_heading:** Wraps heading for aggregated messages (i.e., *The following conditions must be met:*).

**promo_error_item:** Used in conjunction with *promo_error_heading*; wraps each condition required for the promo code.

ToDo
----
- actions
- functional tests


License
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)
