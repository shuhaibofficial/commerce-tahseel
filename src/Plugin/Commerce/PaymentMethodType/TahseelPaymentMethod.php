<?php

namespace Drupal\commerce_tahseel\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the Tahseel payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "tahseel_payment_method",
 *   label = @Translation("Tahseel Payment Methods"),
 *   create_label = @Translation("Tahseel Payment Methods"),
 * )
 */
class TahseelPaymentMethod extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    // eChecks are not reused, so use a generic label.
    return $this->t('Tahseel Payment Methods');
  }
    /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Probably the fields for the Offline payments should be done in the UI.
    return [];
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
