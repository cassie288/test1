# Test Payment Module
Custom payment module enabled in admin panel only.
The payment method will need to call the Restful API endpoint to process the payment.

# Installation (Manual)
* Download the [payment module](https://github.com/cassie288/test1/archive/master.zip), unpack it and upload its contents to a root directory. You should be able to see the module in app/code/local/Mk and app/etc/modules/Mk_Payment.xml
* Flush cache storage in Cache Management

# Configuration
The user is allowed to change:
* Payment title
* Gateway URL
* Order status
* Payment Action (Authorize or Authorize and Capture)

Configuration is located at System > Configuration > Sales > Payment Methods > MK Payment

# Notes
* By default payment action is set to "Authorize and Capture" and the gateway URL is also configured.
* There is no option to enable the payment method in frontend.
* The mock API is always returning the same values even if the values passed are different.
* "txn_ref" from the API is saved in sales_flat_order_payment.last_trans_id
