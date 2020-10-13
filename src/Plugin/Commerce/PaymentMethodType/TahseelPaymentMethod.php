<?php

namespace Drupal\commerce_tahseel\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the Tahseel payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "tahseel_payment_method",
 *   label = @Translation("Tahseel Payment Method"),
 *   create_label = @Translation("Create Tahseel Payment Method"),
 * )
 */
class TahseelPaymentMethod extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    // eChecks are not reused, so use a generic label.
    return $this->t('Tahseel Method');
  }
    

  /**
   * The account types.
   */
  public static function getAccountTypes() {
    return [
      'nationalid' => t('National ID'),
      'residentid' => t('Resident ID'),
    ];
  }

}
