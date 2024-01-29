<?php



/**
 * @copyright  Easebuzz
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Easebuzz\Ebp\Controller\Responce;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Action as AppAction;

use Magento\Framework\App\CsrfAwareActionInterface;	
use Magento\Framework\App\RequestInterface;	
use Magento\Framework\App\Request\InvalidRequestException;

class CallbackEbp extends AppAction implements CsrfAwareActionInterface {

    
    /**
     * @var \Easebuzz\Ebp\Model\PaymentMethod
     */
    protected $_paymentMethod;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    protected $request;
    

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Easebuzz\Ebp\Model\PaymentMethod $paymentMethod
     * @param Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param  \Psr\Log\LoggerInterface $logger
     */
    
    
    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Request\Http $request, \Magento\Sales\Model\OrderFactory $orderFactory, \Easebuzz\Ebp\Model\PaymentMethod $paymentMethod, \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender, \Psr\Log\LoggerInterface $logger    ) {
        
        $this->_paymentMethod = $paymentMethod;
        $this->_orderFactory = $orderFactory;
        $this->_client = $this->_paymentMethod->getClient();
        $this->_orderSender = $orderSender;		
         $this->_logger = $logger;	
        $this->request = $request;
       
        parent::__construct($context);
        
        
    }


    public function createCsrfValidationException(	
        RequestInterface $request 	
    ): ?InvalidRequestException {	
        return null;	
    }	
    public function validateForCsrf(RequestInterface $request): ?bool	
    {	
        return true;	
    }

    /**
     * Handle POST request to Easebuzz callback endpoint.
     */
    public function execute() {
        try {
            if ($this->request->getPost()) {
                $this->logResponceAction();
                $this->_success();
                $this->paymentAction();
            } else {
                $this->_logger->addError("Easebuzz: no post back data received in callback");
                return $this->_failure();
            }
        } catch (Exception $e) {
            $this->_logger->addError("Easebuzz: error processing callback");
            $this->_logger->addError($e->getMessage());
            return $this->_failure();
        }

        // $this->_logger->addInfo("Transaction END from Easebuzz");
        $postdata = $this->getRequest()->getPost();
        if(count($postdata)>0){

            $resultRedirect = $this->resultRedirectFactory->create();	
            if($postdata['status']=='success'){
            $resultRedirect->setPath('checkout/onepage/success');
        }
            else{
            $resultRedirect->setPath('checkout/onepage/failure');	
        }
            return $resultRedirect;
        }
        
    }


    protected function logResponceAction() {

        $ebsalt = $this->_paymentMethod->getConfigData('ebsalt');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $table = $resource->getTableName('ease_buzz_debug');
        //Update Data into table
        $postdata = $this->getRequest()->getPost();
       
        
        if(count($postdata)>0){           
           
            $order_id = $postdata['txnid'];
            $postData_final = json_encode($postdata);          

        }else{
            
            $rawPostBody = file_get_contents('php://input');
            $postdata = json_decode($rawPostBody, true);
            $order_id = $postdata['txnid'];
            $postData_final = json_encode($postdata);
            //$postdata=$postData;
        }
       
        $update_array = array('response_debug_at' => date("YmdHis", time()),
                               'response_body' => $postData_final
                            );

        $genrate_hash=$this->getReverseHashKey($postdata,$ebsalt);
         
        if($postdata['hash']==$genrate_hash){
            try{
                // Start transaction                
                $connection->beginTransaction();
                //$connection->load(1); 
                $connection->update($table, $update_array, "order_id = " . $order_id . "");
                // Commit transaction
                $connection->commit();
            }
            catch (Exception $e) {
                $this->_logger->addError("Easebuzz: error processing callback , order_id=".$order_id);
                $this->_logger->addError($e->getMessage());
    
            } 
        }
        
        
    }

    protected function paymentAction() {
       
        $ebkey = $this->_paymentMethod->getConfigData('ebkey');
        $ebsalt = $this->_paymentMethod->getConfigData('ebsalt');
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $table = $resource->getTableName('sales_order');
        

        if ($this->getRequest()->isPost()) {
            
            $postdata = $this->getRequest()->getPost();
            $iframe=0;
            if(count($postdata)>0){  
                $order_id = $postdata['txnid'];
                
            }else{
                $iframe=1;
                $rawPostBody = file_get_contents('php://input');
                $postdata = json_decode($rawPostBody, true);
                $order_id = $postdata['txnid'];
                
            }
            
            if (isset($postdata ['key']) && ($postdata['key'] == $ebkey)) {
                $ordid = $postdata['txnid'];
                
                // $order = $this->_orderFactory->create()->loadByIncrementId($order_id);
                $message = '';
                $message .= 'orderId: ' . $ordid . "\n";

                $status = $postdata['status'];
                $email = $postdata['email'];
                $firstname = $postdata['firstname'];
                $productinfo = $postdata['productinfo'];
                $amount = $postdata['amount'];
                $txnid = $postdata['txnid'];
                $key = $postdata['key'];
                $responcehase = $postdata['hash'];
              

                if (isset($postdata['status']) && $postdata['status'] == 'success') {
                    $this->_order = $this->_orderFactory->create()->loadByIncrementId($order_id);
                    
                    // $order=$this->_loadOrder($ordid);
                    $responce_info = $ebsalt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $ebkey;
                    $hase = hash('SHA512', $responce_info);

                    foreach ($postdata as $k => $val) {
                        $message .= $k . ': ' . $val . "\n";
                    }
                    if ($hase == $responcehase) {
                        
                        // success	
                        $this->messageManager->addSuccess("Order Successfully placed.<br/> Your transaction is ID - $txnid<br/>");
               
                         // db connection works for version 2.2.2
                        //$order = $objectManager->create('\Magento\Sales\Model\Order') ->load($ordid);
                        $query = 'SELECT status FROM ' . $table . ' WHERE entity_id = '. (int)$ordid . ' LIMIT 1'; 
                        $real_status = $connection->fetchOne($query);
                        
                         if($real_status!='processing'){
                            
                            $this->_order->setState("processing")->setStatus("processing");
                            $this->_order->save();
                            $this->_order->setIsNotified(true);
                            
                        }

                        //old pavan code
                        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        // $order = $objectManager->create('\Magento\Sales\Model\Order') ->load($ordid);
                        // $order->setState("processing")->setStatus("processing");
                        // $order->save();
                        // $order->setIsNotified(true);

                        //---------------------------------------------------------------

                        if($iframe==0) {
                            $postdata = $this->getRequest()->getPost();
                            
                            $redirectUrl = $this->_paymentMethod->getSuccessUrl();
                            $this->_redirect('checkout/onepage/success');
                            // $this->_redirect->setRedirect($redirectUrl);
                        } else {                      
                            echo json_encode(array('status'=>true,'order_id'=>$ordid,'message'=>"Order success.."));                 
                        }
                    } else {
                        $this->_order = $this->_orderFactory->create()->loadByIncrementId($order_id);
                   
                        $this->_createEaseebuzzComment("Easebuzz Response signature does not match. You might have received tampered data", true);
                        $this->_order->cancel()->save();

                        $this->_logger->addError("Easebuzz Response signature did not match ");

                        $this->messageManager->addError("<strong>Error:</strong> Easebuzz Response signature does not match. You might have received tampered data");
                        $this->_redirect('checkout/onepage/failure');
                    }
                } else {
                    // $this->_order=_loadOrder($ordid);
                    $this->_order = $this->_orderFactory->create()->loadByIncrementId($order_id);
                    $historymessage = $message;

                    $this->_createEaseebuzzComment($historymessage);
                    $this->_order->cancel()->save();

                   
                    
                    $this->messageManager->addError("It seems your order has been cancelled. Please try again to place order. <br/>");
                    $this->messageManager->addError("Your transaction id is - $txnid <br/>");
                    
                    if($iframe==0){    
                        $redirectUrl = $this->_paymentMethod->getCancelUrl();
                        $this->_redirect($redirectUrl);
                    }
                    else{
                       echo json_encode(array('status'=>false,'order_id'=>$ordid,'message'=>"Order cancelled.."));
                     }
                    
                }
            }
        }
    }
    
    protected function _loadOrder($order_id) {
        $this->_order = $this->_orderFactory->create()->loadByIncrementId($order_id);
        $postdata = $this->getRequest()->getPost();
        if (!$this->_order && $this->_order->getId()) {
            throw new Exception('Could not find Magento order with id $order_id');
        }
        
    }

   
    protected function _success() {
        $this->getResponse()
                ->setStatusHeader(200);
    }

    protected function _failure() {
        $this->getResponse()
                ->setStatusHeader(400);
    }

    /**
     * Returns the generated comment or order status history object.
     *
     * @return string|\Magento\Sales\Model\Order\Status\History
     */
    protected function _createEaseebuzzComment($message = '') {
        if ($message != '') {
            $message = $this->_order->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }

        return $message;
    }


    function getReverseHashKey($response_array, $s_key){
        
        $reverse_hash_sequence = "udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key";

        // make an array or split into array base on pipe sign.
        $reverse_hash = "";
        $reverse_hash_sequence_array = explode('|', $reverse_hash_sequence);
        $reverse_hash .= $s_key . '|' . $response_array['status'];

        // prepare a string based on reverse hash sequence from the $response_array array.
        foreach ($reverse_hash_sequence_array as $value) {
            $reverse_hash .= '|';
            $reverse_hash .= isset($response_array[$value]) ? $response_array[$value] : '';
        }
        // generate reverse hash key using hash function(predefine) and return
       
        return strtolower(hash('sha512', $reverse_hash));
    }
}
