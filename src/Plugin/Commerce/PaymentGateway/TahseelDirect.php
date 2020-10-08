<?php

namespace Drupal\commerce_tahseel\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;

use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Provides the Worldpay direct payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "tahseel_direct",
 *   label = @Translation("Tahseel (Direct)"),
 *   display_label = @Translation("Tahseel"),
 *    forms = {
 *     "add-payment-method" = "Drupal\commerce_tahseel\PluginForm\Onsite\TahseelDirectForm",
 *   },
 *   payment_method_types = {"tahseel_payment_method"},

 * )
 */
class TahseelDirect extends OnsitePaymentGatewayBase implements TahseelDirectInterface {


    /**
   * Payflow test API URL.
   */
  const TAHSEEL_API_TEST_URL = 'https://f2c80379-c133-418a-a409-684663b57576.mock.pstmn.io/sadadpost';  // Rest

  /**
   * Payflow production API URL.
   */
  const TAHSEEL_API_URL = 'http://localhost:7800/BillManage'; //Local Soap service

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ClientInterface $client, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->httpClient = $client;
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('http_client'),
      $container->get('commerce_price.rounder')
    );
  }	

  /**
   * Returns the Api URL.
   */
  protected function getApiUrl() {
    return $this->getMode() == 'test' ? self::TAHSEEL_API_TEST_URL : self::TAHSEEL_API_URL;
  }  
		
	
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'nationalid' => '',
        'residentid' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['nationalid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('National ID'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['nationalid'],
    ];
    $form['residentid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resident ID'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['residentid'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['nationalid'] = $values['nationalid'];
      $this->configuration['residentid'] = $values['residentid'];
      
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['nationalid'] = $values['nationalid'];
      $this->configuration['residentid'] = $values['residentid'];
    
    }
  }
  /**
  * {@inheritdoc}
  */
	public function getNotifyUrl() {
    return Url::fromRoute('commerce_payment.notify', [
          'commerce_payment_gateway' => $this->parentEntity->id(),
        ], ['absolute' => TRUE]);
    }
  
  
    /**
   * Attempt to validate payment information according to a payment state.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to validate.
   * @param string|null $payment_state
   *   The payment state to validate the payment for.
   */
  protected function validatePayment(PaymentInterface $payment, $payment_state = 'new') {
    $this->assertPaymentState($payment, [$payment_state]);

    $payment_method = $payment->getPaymentMethod();
    if (empty($payment_method)) {
      throw new InvalidArgumentException('The provided payment has no payment method referenced.');
    }

    switch ($payment_state) {
      case 'new':
        if ($payment_method->isExpired()) {
          throw new HardDeclineException('The provided payment method has expired.');
        }

        break;

      case 'authorization':
        if ($payment->isExpired()) {
          throw new \InvalidArgumentException('Authorizations are guaranteed for up to 29 days.');
        }
        if (empty($payment->getRemoteId())) {
          throw new \InvalidArgumentException('Could not retrieve the transaction ID.');
        }
        break;
    }
  }


  /**
   * Creates a payment method with the given payment details.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $payment_details
   *   The gateway-specific payment details.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // TODO: Implement createPaymentMethod() method.
    $expires = time() + (30 * 24 * 60 * 60);
    // The remote ID returned by the request.
    $remote_id = $payment_method->getOwnerId();

    $payment_method->setRemoteId($remote_id);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();

  }



  /**
   * Creates a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param bool $capture
   *   Whether the created payment should be captured (VS authorized only).
   *   Allowed to be FALSE only if the plugin supports authorizations.
   *
   * @throws \InvalidArgumentException
   *   If $capture is FALSE but the plugin does not support authorizations.
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    // TODO: Implement createPayment() method.
	
	$this->validatePayment($payment, 'new');

    try {
      $data = $this->executeTransaction([
        'trxtype' => $capture ? 'S' : 'A',
        'amt' => $this->rounder->round($payment->getAmount())->getNumber(),
        'currencycode' => $payment->getAmount()->getCurrencyCode(),
        'origid' => $payment->getPaymentMethod()->getRemoteId(),
        'verbosity' => 'HIGH',
        // 'orderid' => $payment->getOrderId(),
      ]);
      if (empty($data)) {
        throw new HardDeclineException('Could not charge the payment method. Response: ' );
      }

      //$next_state = $capture ? 'completed' : 'authorization';
      //$payment->setState($next_state);
      if (!$capture) {
        $payment->setExpiresTime($this->time->getRequestTime() + (86400 * 29));  //may be Payment time+40 mints
      }

      //$payment
       // ->setRemoteId($data->PaymentDetails->Details->Refid)
        //->setRemoteState('3')
        //->save();
        //payment->getOrder() //Returns an object of order interface
        //payment->getOrder()->getOrderNumber() //should retun the order number of current customer not parent sadlt it returns NULL ?
        //$orderid = $payment->getOrderId(); //prints parent order id we don't need it,bcz its internal order id
        //
        $billing_account = $data->PaymentDetails->Details->Refid; //From Postman Mock since no service available for now
        $billing_amount = $this->rounder->round($payment->getAmount())->getNumber();
        $state_interface= $payment->getOrder()->getState();            //\Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface

        $soap_response_status = $this->executeSoapTransaction($billing_account,$billing_amount)->MsgRsHdr->ResponseStatus->StatusCode;
      if(strcmp($soap_response_status,'I000000')) ///if both string are equal then strcmp() returns 0 so unless there is error we will never enter here
      {
        throw new HardDeclineException('Could not charge the payment method.Soap Response Error: ' );
      }  


        
        $payment->setState('pending');
        $payment->setRemoteId($data->PaymentDetails->Details->Refid);
        $current_state  = $payment->getOrder()->getState()->getOriginalId();
        $current_state2 = $payment->getOrder()->getState()->getId();
        $current_state3 = $payment->getOrder()->getState()->getLabel();

        $payment->save();
        
        $sadad_ref_id=$data->PaymentDetails->Details->Refid;
        $sadad_bill_id='002';//$data->PaymentDetails->Details->BiilerCode;
        drupal_set_message(t('Kindly Pay The SADAD Bill.'), 'status');
        //drupal_set_message(t('Order Number is empty : %orderid', array('%orderid' => $value_of_order)), 'status');
        //drupal_set_message(t('Order isSet : %isset', array('%isset' => $order_isset)), 'status');
        drupal_set_message(t('Original iD  : %order', array('%order' => gettype($current_state).$current_state)), 'status');
        drupal_set_message(t('getId  : %order', array('%order' => gettype($current_state2).$current_state2)), 'status');
        drupal_set_message(t('getLabel  : %order', array('%order' => gettype($current_state3).$current_state3)), 'status');
        drupal_set_message(t('Amount To Pay  : %amount', array('%amount' => $payment->getAmount())), 'status');
        drupal_set_message(t('<strong>Soap Reponse status:%response</strong>',array('%response' => $soap_response_status)), 'status');
        drupal_set_message(t('<strong>SADAD Bill Ref:%srefid</strong>',array('%srefid' => $sadad_ref_id)), 'status');
        drupal_set_message(t('<strong>SADAD Biller Name:Ministry of Finance</strong>'), 'status');
        drupal_set_message(t('<strong>SADAD Biller Code:%billid</strong>',array('%billid' => $sadad_bill_id)), 'status');
    }
    catch (RequestException $e) {
      throw new HardDeclineException('Could not charge the payment method.');
    }
	
	
	
	
	
  }


  /**
   * Deletes the given payment method.
   *
   * Both the entity and the remote record are deleted.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // TODO: Implement deletePaymentMethod() method.
    $payment_method->delete();
  }
  
  
  /**
   * Processes the notification request.
   *
   * This method should only be concerned with creating/completing payments,
   * the parent order does not need to be touched. The order state is updated
   * automatically when the order is paid in full, or manually by the
   * merchant (via the admin UI).
   *
   * Note:
   * This method can't throw exceptions on failure because some payment
   * providers expect an error response to be returned in that case.
   * Therefore, the method can log the error itself and then choose which
   * response to return.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The response, or NULL to return an empty HTTP 200 response.
   */  
  public function onNotify(Request $request) {



	//  TODO: Implement onNotify() method.
	  $content = $request->getMethod() === 'POST' ? $request->getContent() : FALSE;
    if (!$content) {
      $this->logger->error('There is no response was received');
      throw new PaymentGatewayException();
    }

    $content_body =  json_decode($content);
    // Hook to allow other modules to access the POST content from the WorldPay Payment response.
  

    // Get and check the VendorTxCode.
    $txCode = $content_body->BillAccount !== NULL ? $content_body->BillAccount : FALSE;

    //strcmp($str1,$str2) retrun zero if both st1,st2 are equal (if tahseel returns bad notfication not like 'Succeeded' it throw gateway exception) 

    if (empty($txCode) || empty($content_body->PaymentStatusCode)  ) {
       $this->logger->error('No Transaction code have been returned.');
       throw new PaymentGatewayException();
   }

    //$amount = new Price($content_body->PaymentDetails->Details->Amount, $content_body->PaymentDetails->Details->Currency);
    $amount = new Price($content_body->PaymentAmount, 'USD');
    //$payment = $this->entityTypeManager->getStorage('commerce_payment')->loadByRemoteId($content_body->PaymentDetails->Details->Refid);
    $payment = $this->entityTypeManager->getStorage('commerce_payment')->loadByRemoteId($content_body->BillAccount);


    if (!empty($payment)) {
      
      $payment->setAmount($amount);
      $payment->setState('completed');
      $payment->save();



    $sample_response = array("Payment"=>"Success", "Code"=>"I0000000");
    $output = json_encode($sample_response); 
    

    $response = new Response();
    $response->setStatusCode(200);
    $response->setContent($output);
    $response->headers->set('Content-Type', 'application/json');
   

    return $response;
	
  }
}


  
  /**
   * Prepares the request body to name/value pairs.
   *
   * @param array $parameters
   *   The request parameters.
   *
   * @return string
   *   The request body.
   */
  protected function prepareBody(array $parameters = []) {
    $parameters = $this->getParameters($parameters);

    $values = [];
    foreach ($parameters as $key => $value) {
      $values[] = strtoupper($key) . '=' . $value;
    }

    return implode('&', $values);
  }

  /**
   * Merge default Payflow parameters in with the provided ones.
   *
   * @param array $parameters
   *   The parameters for the transaction.
   *
   * @return array
   *   The new parameters.
   */
  protected function getParameters(array $parameters = []) {
    $defaultParameters = [
      'tender' => 'C',
      'partner' => 'test',//$this->getPartner(),
      'vendor' => 'test',//$this->getVendor(),
      'user' => 'test',//$this->getUser(),
      'pwd' => 'test',//$this->getPassword(),
    ];

    return $parameters + $defaultParameters;
  }

  /**
   * Prepares the result of a request.
   *
   * @param string $body
   *   The result.
   *
   * @return array
   *   An array of the result values.
   */
  protected function prepareResult($body) {
    

    return json_decode($body);
  }

  /**
   * Post a transaction to the Payflow server and return the response.
   *
   * @param array $parameters
   *   The parameters to send (will have base parameters added).
   *
   * @return array
   *   The response body data in array format.
   */
  protected function executeTransaction(array $parameters) {
    $body = $this->prepareBody($parameters);

    $response = $this->httpClient->post($this->getApiUrl(), [
      'headers' => [
        'Content-Type' => 'text/namevalue',
        'Content-Length' => strlen($body),
      ],
      'body' => $body,
      'timeout' => 0,
    ]);
    return $this->prepareResult($response->getBody()->getContents());
  }

    /**
   * Post a transaction to the Payflow server and return the response.
   *
   * @param array $parameters
   *   The parameters to send (will have base parameters added).
   *
   * @return object
   *   The response body data in array format.
   */


  protected function executeSoapTransaction($billing_account,$billing_amount) {
    $wsdl   = "http://localhost:7800/BillManage?WSDL";
    $client = new \SoapClient($wsdl, array('trace'=>1,'soap_version' => SOAP_1_2));  // The trace param will show you errors stack
    
    // web service input params
    
    $request_param = array(
      "MsgRqHdr" => ["RqUID" => "portals4d46s4ds64s64d","SCId" =>"PORTAL" , "ServiceId" => "BillManage" , "FuncId" => "25000000"],
        "Body" => ["BillList" => [
            "BillInfo" => ["AgencyId" => "041001000000002000","BillCategory"=>"APIBill","BillingAcct"=>$billing_account,"Amt"=>$billing_amount,"BillAction"=>"I","DisplayLabelAr"=>" جامعة دار ا ل حكمة الاهلية ","DisplayLabelEn"=>"Faculty of DAR ALHEKMA","BillDueDt"=>"2020-04-08","BillRefInfo"=>"API",
                     "RevenueEntryList" => ["RevenueEntryInfo"=>["BenAgencyId" => "7770070000000000", "GFSCode"=>"1421901", "Amt"=>$billing_amount]
                     ] //revenue entry list end
            
            ]//Billinfo ends
        ]//Billits end
    
    
        ] //Body ends
        
    );
    
    try
    {
        $responce_param = $client->BillManage($request_param);
       //$responce_param =  $client->call("webservice_methode_name", $request_param); // Alternative way to call soap method
      // echo "<p>".var_dump ($responce_param->MsgRsHdr->ResponseStatus->StatusCode)."</p>"; 
    } 
    catch (Exception $e) 
    { 
        echo "<h2>Exception Error!</h2>"; 
        echo $e->getMessage(); 
    }
    return $responce_param;

  }






  
  
  
}