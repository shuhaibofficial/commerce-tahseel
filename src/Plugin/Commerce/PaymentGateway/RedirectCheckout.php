<?php

namespace Drupal\commerce_tahseel_gateway\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides the QuickPay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "quickpay_redirect_checkout",
 *   label = @Translation("Tahseel (Redirect to Tahseel)"),
 *   display_label = @Translation("Tahseel"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_tahseel_gateway\PluginForm\RedirectCheckoutForm",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase
{
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'national_id' => '',
        'iqama_id' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['national_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('National ID'),
      '#description' => $this->t('This is the National ID for Saudi Nationals.'),
      '#default_value' => $this->configuration['national_id'],
      '#required' => TRUE,
    ];

    $form['iqama_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iqama ID'),
      '#description' => $this->t('This is the Iqama ID for Expat .'),
      '#default_value' => $this->configuration['iqama_id'],
      '#required' => TRUE,
    ];
	
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['national_id'] = $values['national_id'];
      $this->configuration['iqama_id'] = $values['iqama_id'];
    }
  }

  /**
   * Returns an array of languages supported by Quickpay.
   *
   * @return array
   *   Array with key being language codes, and value being names.
   */
  protected function getLanguages()
  {
    return [
      'da' => $this->t('Danish'),
      'de' => $this->t('German'),
      'en' => $this->t('English'),
      'fo' => $this->t('Faeroese'),
      'fr' => $this->t('French'),
      'gl' => $this->t('Greenlandish'),
      'it' => $this->t('Italian'),
      'no' => $this->t('Norwegian'),
      'nl' => $this->t('Dutch'),
      'pl' => $this->t('Polish'),
      'se' => $this->t('Swedish'),
    ];
  }

  /**
   * Information about all supported cards.
   *
   * @return array
   *   Array with card name and image.
   */
  protected function getQuickpayCards()
  {
    $images_path = drupal_get_path('module', 'commerce_tahseel_gateway') . '/images/';
    return [
      'dankort' => [
        'name' => $this->t('Dankort'),
        'image' => $images_path . 'dan.jpg',
      ],
      'visa' => [
        'name' => $this->t('Visa'),
        'image' => $images_path . 'visa.jpg',
      ],
      'visa-dk' => [
        'name' => $this->t('Visa, issued in Denmark'),
        'image' => $images_path . 'visa.jpg',
      ],
      '3d-visa' => [
        'name' => $this->t('Visa, using 3D-Secure'),
        'image' => $images_path . '3d-visa.gif',
      ],
      '3d-visa-dk' => [
        'name' => $this->t('Visa, issued in Denmark, using 3D-Secure'),
        'image' => $images_path . '3d-visa.gif',
      ],
      'visa-electron' => [
        'name' => $this->t('Visa Electron'),
        'image' => $images_path . 'visaelectron.jpg',
      ],
      'visa-electron-dk' => [
        'name' => $this->t('Visa Electron, issued in Denmark'),
        'image' => $images_path . 'visaelectron.jpg',
      ],
      '3d-visa-electron' => [
        'name' => $this->t('Visa Electron, using 3D-Secure'),
      ],
      '3d-visa-electron-dk' => [
        'name' => $this->t('Visa Electron, issued in Denmark, using 3D-Secure'),
      ],
      'mastercard' => [
        'name' => $this->t('Mastercard'),
        'image' => $images_path . 'mastercard.jpg',
      ],
      'mastercard-dk' => [
        'name' => $this->t('Mastercard, issued in Denmark'),
        'image' => $images_path . 'mastercard.jpg',
      ],
      'mastercard-debet-dk' => [
        'name' => $this->t('Mastercard debet card, issued in Denmark'),
        'image' => $images_path . 'mastercard.jpg',
      ],
      '3d-mastercard' => [
        'name' => $this->t('Mastercard, using 3D-Secure'),
      ],
      '3d-mastercard-dk' => [
        'name' => $this->t('Mastercard, issued in Denmark, using 3D-Secure'),
      ],
      '3d-mastercard-debet-dk' => [
        'name' => $this->t('Mastercard debet, issued in Denmark, using 3D-Secure'),
      ],
      '3d-maestro' => [
        'name' => $this->t('Maestro'),
        'image' => $images_path . '3d-maestro.gif',
      ],
      '3d-maestro-dk' => [
        'name' => $this->t('Maestro, issued in Denmark'),
        'image' => $images_path . '3d-maestro.gif',
      ],
      'jcb' => [
        'name' => $this->t('JCB'),
        'image' => $images_path . 'jcb.jpg',
      ],
      '3d-jcb' => [
        'name' => $this->t('JCB, using 3D-Secure'),
        'image' => $images_path . '3d-jcb.gif',
      ],
      'diners' => [
        'name' => $this->t('Diners'),
        'image' => $images_path . 'diners.jpg',
      ],
      'diners-dk' => [
        'name' => $this->t('Diners, issued in Denmark'),
        'image' => $images_path . 'diners.jpg',
      ],
      'american-express' => [
        'name' => $this->t('American Express'),
        'image' => $images_path . 'amexpress.jpg',
      ],
      'american-express-dk' => [
        'name' => $this->t('American Express, issued in Denmark'),
        'image' => $images_path . 'amexpress.jpg',
      ],
      'fbg1886' => [
        'name' => $this->t('Forbrugsforeningen'),
        'image' => $images_path . 'forbrugsforeningen.gif',
      ],
      'paypal' => [
        'name' => $this->t('PayPal'),
        'image' => $images_path . 'paypal.jpg',
      ],
      'sofort' => [
        'name' => $this->t('Sofort'),
        'image' => $images_path . 'sofort.png',
      ],
      'viabill' => [
        'name' => $this->t('ViaBill'),
        'image' => $images_path . 'viabill.png',
      ],
    ];
  }
}
