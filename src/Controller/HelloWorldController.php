<?php 
namespace Drupal\commerce_tahseel\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */
class HelloWorldController extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {
    $build = [
      '#markup' => $this->t('Hello World!'),
    ];
    return $build;
  }

}