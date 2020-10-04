CONTENTS OF THIS FILE
---------------------
Worlppay Intergration (Direct).
- Create a payment gateway Direct.
- Use the secret key Details from the Worlpay logged in account settings.
- Use the account in test account settings to run transactions.

Worlpay Intergration (Redirect).
----------------------
- Create a payment gateway.
- In Worldpay set the Installation instructions. For this module to work properly you must configure a few specific options in your RBS WorldPay account under Installation Administration settings:
    Payment Response URL must be set to: <wpdisplay item=MC_callback-ppe empty="http://yoursitename/payment/notify/MACHINE_NAME_OF_PAYMENT_GATEWAY"/>;
    Payment Response enabled? must be enabled
    Enable the Shopper Response should be enabled to get the Commerce response page.
    Shopper Redirect URL and set the value to be <wpdisplay item=MC_callback/>. Worldpay help document.
    SignatureFields must be set to: instId:amount:currency:cartId:MC_orderId:MC_callback
- Set the in the payment plugin the Installation ID, Site ID, Installation password. These are found on the worldpay account.
- The secret key is a hash key you will need to generate this your self  using something like https://www.md5hashgenerator.com/
- The resultC.html &amp; resultY.html do not need to be uploaded to Worldpay site as we are using the templates located at commerce_worldpay/templates

API Docs: https://developer.worldpay.com/jsonapi/docs/
Shopping Carts: https://business.worldpay.com/shopping-carts

Internal (API): https://business.worldpay.com/developer-support/direct-integration
External (iFrame & redirect): https://business.worldpay.com/developer-support/redirect-integration