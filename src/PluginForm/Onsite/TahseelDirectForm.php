<?php

namespace Drupal\commerce_tahseel\PluginForm\Onsite;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
//use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

class TahseelDirectForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
	$configuration = $this->entity->getPaymentGateway()->get('configuration');
	
	if (!empty($configuration['nationalid'])) {
      $form['payment_details']['nationalid'] = [
        '#markup' => $configuration['nationalid'],
      ];
    }

    if (!empty($configuration['residentid'])) {
      $form['payment_details']['residentid'] = [
        '#markup' => $configuration['residentid'],
      ];
    }
	
	// Dummy key element for the offline payment.
    $form['payment_details']['key'] = [
      '#type' => 'value',
      '#value' => 'no-value',
    ];
	
	return $form;
  }

}