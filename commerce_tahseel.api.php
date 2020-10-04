<?php

/**
 * Respond to WorldPay response POST after a Commerce payment
 *
 * This hook allows modules to access the payment response POST from WorldPay after a commerce payment is made through WorldPay. It contains transaction details such as Worldpay ID and Cardholder Name.
 *
 * @param $request
 *      The WorldPay payment response POST.
 */

function hook_commerce_tahseel_payment_response($request) {

}