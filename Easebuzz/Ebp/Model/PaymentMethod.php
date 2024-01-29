<?php

/**
 *
 * @copyright  Easebuzz
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Easebuzz\Ebp\Model;

use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod {

    protected $_code = 'ebp';
    protected $_isInitializeNeeded = true;
    //$this->logger->addDebug("=======END ORDER======");
    /**
     * @var \Magento\Framework\Exception\LocalizedExceptionFactory
     */
    protected $_exception;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $_transactionRepository;

    /**
     * @var Transaction\BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    protected $adnlinfo;
    protected $title;

    /**
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Exception\LocalizedExceptionFactory $exception
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\UrlInterface $urlBuilder, \Magento\Framework\Exception\LocalizedExceptionFactory $exception, \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository, \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder, \Magento\Sales\Model\OrderFactory $orderFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory, \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, \Magento\Payment\Helper\Data $paymentData, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Payment\Model\Method\Logger $logger, \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_exception = $exception;
        $this->_transactionRepository = $transactionRepository;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_orderFactory = $orderFactory;
        $this->_storeManager = $storeManager;

        parent::__construct(
                $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data
        );
    }

    /**
     * Instantiate state and set it to state object.
     *
     * @param string                        $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     */
    public function initialize($paymentAction, $stateObject) {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    public function getPostHTML($order, $storeId = null) {
        $timestamp = date("YmdHis", time());
        $tmpvar = $this->getGateway();
        if ($tmpvar == 'easebuzz') {
            $environment = $this->getConfigData('environment');
            $ebkey = $this->getConfigData('ebkey');
            $ebsalt = $this->getConfigData('ebsalt');
            $debug= $this->getConfigData('debug');
            $checkout= $this->getConfigData('enable_iframe');
            $txnid = $order->getIncrementId();
            $amount = $order->getGrandTotal();
            $amount = number_format((float) $amount, 2, '.', '');

            $action =  'https://pay.easebuzz.in/payment/initiateLink';
             
            if ($environment == 'sandbox')
                $action = 'https://testpay.easebuzz.in/payment/initiateLink';

            $billingAddress = $order->getBillingAddress();
            $productInfo = "Magento Transactions";

            $firstname = trim($billingAddress->getData('firstname'));
            $email = trim($billingAddress->getData('email'));
            $phone = trim($billingAddress->getData('telephone'));
            $address1 = trim($billingAddress->getData('street'));
            $city = trim($billingAddress->getData('city'));
            $state = $billingAddress->getData('region');
            if ($state !== null) {
                $state = trim($state);
            }
            $postcode = trim($billingAddress->getData('postcode'));
            $country_id = trim($billingAddress->getData('country_id'));
            $surl = self::getEasebuzzUrl();
            $furl = self::getEasebuzzUrl();            
         
            $request_Info = $ebkey . '|' . $txnid . '|' . $amount . '|' . $productInfo . '|' . $firstname . '|' . $email . '|||||||||||' . $ebsalt;
            $hash = hash('SHA512', $request_Info);     
            
            // form post action code must be added here
            if ($checkout){

                $form= array('key'=> $ebkey,
                    'txnid'=> $txnid,
                    'amount'=> $amount,
                    'email'=> $email,
                    'phone'=> $phone,
                    'firstname'=> $firstname,
                    'udf1'=>'',
                    'udf2'=> '',
                    'udf3'=> '',
                    'udf4'=> '',
                    'udf5'=> '',
                    'hash'=> $hash,
                    'productinfo'=>$productInfo,
                    'udf6'=>'',
                    'udf7'=> '',
                    'udf8'=> '',
                    'udf9'=>'',
                    'udf10'=> '', 
                    'furl'=> $furl,
                    'surl'=> $surl  
                );

                
                function curlCall($url, $params_array){
                    // Initializes a new session and return a cURL.
                    $cURL = curl_init();
            
                    ini_set('display_errors', 1);
                    ini_set('display_startup_errors', 1);
                    error_reporting(E_ALL);
            
                    // Set multiple options for a cURL transfer.
                    curl_setopt_array(
                        $cURL,
                        array(
                            CURLOPT_URL => $url,
                            CURLOPT_POSTFIELDS => $params_array,
                            CURLOPT_POST => true,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36',
                            CURLOPT_SSL_VERIFYHOST => 0,
                            CURLOPT_SSL_VERIFYPEER => 0
                        )
                    );
            
                    // Perform a cURL session
                    $result = curl_exec($cURL);
            
            
                    // check there is any error or not in curl execution.
                    if (curl_errno($cURL)) {
                        $cURL_error = curl_error($cURL);
                        if (empty($cURL_error))
                            $cURL_error = 'Server Error';
            
                        return array(
                            'curl_status' => 0,
                            'error' => $cURL_error
                        );
                    }
            
                    $result = trim($result);
                    $result_response = json_decode($result);
                    return $result_response;
                }
            

                $curl_result = curlCall($action, http_build_query($form));
                $accesskey = ($curl_result->status === 1) ? $curl_result->data : null;
                
                
                

                //Insert Log
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
                $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();
                $table = $resource->getTableName('ease_buzz_debug');
                //Insert Data into table
                #$sqlInsert = "Insert Into " . $table . " (order_id, request_debug_at, response_debug_at, request_body, response_body) Values ( '" . $txnid . "', '" . $timestamp . "', 'response_debug_at', '" . (json_encode($request_Info)) . "', 'response_info')";
                $sqlInsert = "Insert Into " . $table . " (order_id, request_debug_at, response_debug_at, request_body, response_body) Values ( '" . $txnid . "', '" . $timestamp . "', 'response_debug_at', '" . (json_encode($request_Info)) . "','$surl' )";
                $connection->query($sqlInsert);
                
                return array("access_key"=>$accesskey,'key'=>$ebkey,'checkout'=>$checkout); 
               
            }


            else {
                $action =  'https://pay.easebuzz.in/pay/secure';
                 
                if ($environment == 'sandbox'){
                    $action = 'https://testpay.easebuzz.in/pay/secure'; 
                    }
                
                $html = "<form action=\"" . $action . "\" method=\"post\" id=\"easebuzz_form\" name=\"easebuzz_form\">
                <input type=\"hidden\" name=\"key\" value=\"" . $ebkey . "\" />
                <input type=\"hidden\" name=\"txnid\" value=\"" . $txnid . "\" />
                <input type=\"hidden\" name=\"amount\" value=\"" . $amount . "\" />
                <input type=\"hidden\" name=\"productinfo\" value=\"" . $productInfo . "\" />
                <input type=\"hidden\" name=\"firstname\" value=\"" . $firstname . "\" />
                <input type=\"hidden\" name=\"phone\" value=\"" . $phone . "\" />
                <input type=\"hidden\" name=\"email\" value=\"" . $email . "\" />
                <input type=\"hidden\" name=\"surl\" value=\"" . $surl . "\" />
                <input type=\"hidden\" name=\"furl\" value=\"" . $furl . "\" />
                <input type=\"hidden\" name=\"hash\" value=\"" . $hash . "\" />
                <input type=\"hidden\" name=\"address1\" value=\"" . $address1 . "\" />
                <input type=\"hidden\" name=\"city\" value=\"" . $city . "\" />
                <input type=\"hidden\" name=\"state\" value=\"" . $state . "\" />
                <input type=\"hidden\" name=\"country\" value=\"" . $country_id . "\" />
                <input type=\"hidden\" name=\"zipcode\" value=\"" . $postcode . "\" />
                <button style='display:none' id='submit_easebuzz_payment_form' name='submit_easebuzz_payment_form' >Pay Now</button>
                </form>
                <script type=\"text/javascript\">document.getElementById(\"easebuzz_form\").submit();</script>
                ";

            // Insert Log
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $table = $resource->getTableName('ease_buzz_debug');
            //Insert Data into table
            $sqlInsert = "Insert Into " . $table . " (order_id, request_debug_at, response_debug_at, request_body, response_body) Values ( '" . $txnid . "', '" . $timestamp . "', 'response_debug_at', '" . (json_encode($request_Info)) . "', 'response_info')";
            $connection->query($sqlInsert);
            return array('html'=>$html,'checkout'=>$checkout);

            }
                
        }
        
    }

    public function getOrderPlaceRedirectUrl($storeId = null) {
        return $this->_getUrl('ebp/checkout/start', $storeId);
    }

    public function getTitle() {
        $tmpvar = $this->getGateway();
        return $this->title;
    }

    /**
     * Get  Gateway PayUM or Citrus.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getGateway() {
        $this->adnlinfo = 'easebuzz';
        $this->title = 'Easebuzz Payment';

        return $this->adnlinfo;
    }

    /**
     * Get return URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    //AA may not be required
    public function getSuccessUrl($storeId = null) {
        return $this->_getUrl('checkout/onepage/success', $storeId);
    }

    /**
     * Get notify (Responce) URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getEasebuzzUrl($storeId = null) {
        //return $this->_getUrl('ebp/responce/callbackebp', $storeId, false);
    
        return $this->_getUrl('ebp/responce/callbackebp', $storeId, null);
    
    }


    /*
     * Get cancel URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    //AA Not required
    public function getCancelUrl($storeId = null) {
        
        return $this->_getUrl('checkout/onepage/failure', $storeId);
    }

    /**
     * Get cancel URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    //AA Done
    public function getEnquirylUrl($txnid, $storeId = null) {
        return $this->_getUrl('ebp/checkout/enquiry', $storeId) . '/txnid/' . $txnid;
    }

    /**
     * Build URL for store.
     *
     * @param string    $path
     * @param int       $storeId
     * @param bool|null $secure
     *
     * @return string
     */
    //AA Done
    protected function _getUrl($path, $storeId, $secure = null) {
        $store = $this->_storeManager->getStore($storeId);
        $data = $this->_urlBuilder->getUrl($path);
        return $this->_urlBuilder->getUrl(
                        $path, ['_store' => $store, '_secure' => $secure === null ? $store->isCurrentlySecure() : $secure]
        );
    }
    
    public function getUrl($path, $storeId=null, $secure = null) {
        $store = $this->_storeManager->getStore($storeId);
        $data = $this->_urlBuilder->getUrl($path);
        
        return $this->_urlBuilder->getUrl(
                        $path, ['_store' => $store, '_secure' => $secure === null ? $store->isCurrentlySecure() : $secure]
        );
    }
    

}
