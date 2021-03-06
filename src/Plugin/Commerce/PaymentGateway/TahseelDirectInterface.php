<?php

namespace Drupal\commerce_tahseel\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;

interface TahseelDirectInterface extends OnsitePaymentGatewayInterface, SupportsNotificationsInterface   {
    public function getNotifyUrl();
}