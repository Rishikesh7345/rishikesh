<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @category Eighteentech
 * @package  Eighteentech_SplitOrder
 *
 */
 
namespace Eighteentech\SplitOrder\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\Session;
use Magento\Quote\Model\Cart\CartTotalRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ResourceConnection;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_ENABLE = "splitorder/general/isenable";
    const MODULE_CUSTOMER_GROUP = "splitorder/general/splitordergroupid";
    
    protected $order;
    protected $_productloader;
    protected $_customerSession;
    protected $cartTotalRepository;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    protected $quoteFactory;
    
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Product $product,
        CartRepositoryInterface $cartRepositoryInterface,
        CartManagementInterface $cartManagementInterface,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        Order $order,
        Cart $cart,
        ProductFactory $_productloader,
        Session $customerSession,
        CartTotalRepository $cartTotalRepository,
        ResourceConnection $resourceConnection,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository        
    ) {
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->order = $order;
        $this->cart = $cart;
        $this->_productloader = $_productloader;
        $this->_customerSession = $customerSession;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->resourceConnection = $resourceConnection;
        $this->quoteFactory = $quoteFactory;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    public function getDefaultConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    public function isModuleEnabled()
    {
        return (bool) $this->getDefaultConfig(self::MODULE_ENABLE);
    }
    
    public function getConfigGroupId()
    {
        return (int) $this->getDefaultConfig(self::MODULE_CUSTOMER_GROUP);
    }
    
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
    
    public function getGroupId()
    {
        $customerGroup=0;
        if ($this->_customerSession->isLoggedIn()) {
            $customerGroup=$this->_customerSession->getCustomer()->getGroupId();
        }
        return $customerGroup;
    }
    
     /**
      * Create Order On Your Store
      *
      * @param array $orderData
      * @return array
      *
      */
    public function createMageOrder($orderData)
    {
		//echo "<pre>";
		//  print_r($orderData);
		 // echo count($orderData['items']);
		 //  die('rsarerer');
        try {
            $store=$this->_storeManager->getStore();
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            $customer=$this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($orderData['email']);// load customet by email address
            $cartId = $this->cartManagementInterface->createEmptyCart(); //Create empty cart
            $quote = $this->cartRepositoryInterface->get($cartId); // load empty cart quote        
            $quote->setStore($store);
          // if you have allready buyer id then you can load customer directly
        // $customer= $this->customerRepository->getById($customer->getEntityId());
      
            $quote->setCurrency();
            $guest = true;
    
        // Set Customer Data on Qoute, Do not create customer.
            $quote->setCustomerFirstname($orderData['address']['firstname']);
            $quote->setCustomerLastname($orderData['address']['lastname']);
            $quote->setCustomerEmail($orderData['email']);
            $quote->setCustomerIsGuest($guest);
            $quote->setParentOrderId($orderData['parent_order_id']);
            $quote->save();
 //echo "<pre>";    
 //echo count($orderData['items']);   
//print_r($orderData['items']); die;
 //echo "<br>************************************************************/";
         //add items in quote
         //echo count($orderData['items']);
      //die('kkkkkkkkkkkkkk');
      
       if(count($orderData['items'])>1){
            foreach ($orderData['items'] as $itemobjs) {				
				foreach ($itemobjs as $itemobj) {
					$product = $this->_productloader->create()->load($itemobj['product']);
				    $obj = new \Magento\Framework\DataObject();
					$obj->setData($itemobj);
					$quote->addProduct($product, $obj);	
					$quote->save();					
					// get last quote id and last inserted item id					
					$lastInsrtquoteId = $quote->getId();					
					$items = $quote->getAllVisibleItems();
					$max = 0;
					$lastItem = null;
					foreach ($items as $item){
						if ($item->getId() > $max) {
							$max = $item->getId();
							$lastItem = $item;
						}
					}
					if ($lastItem){						
						//echo $lastItem->getId();
						$item = $quote->getItemById($lastItem->getId());
						if (!$item) {
							continue;
						}
					
						$item->setBoxId($itemobj['box_id']);
						$item->setBoxName($itemobj['box_name']);
						$item->setBoxType($itemobj['box_type']);
						$item->setBoxProductId($itemobj['box_product_id']);
						$item->save();
					}						
				}
				$quote->save();
            }
            
           } else {
			  //die('bbbbbbbbbbbbbbbbbbbbbb');
			 //echo "<pre>";			 
			 //ini_set('display_errors', 1);
			// print_r($orderData['items']);
			// die('bbbbbbbbbbbbbbbbbbbbbb');
			
			if($orderData['isboxType']==0){
				
				foreach ($orderData['items'] as $itemobject) {
					$index=0;
					$quote->save();
					$product = $this->_productloader->create()->load($itemobject['product']);
					$object = new \Magento\Framework\DataObject();
					$object->setData($itemobject);           
				   
					try{
							$quote->addProduct($product, $object);
						} catch (\Exception $e) {
							echo $e->getMessage();
							error_log($e->getMessage());					
						}
					// get last quote id and last inserted item id
						$quote->save();
						$lastInsrtquoteId = $quote->getId();	 		
						$items = $quote->getAllVisibleItems();
						$max = 0;
						$lastItem = null;
						
						foreach ($items as $item){
							if ($item->getId() > $max) {
								$max = $item->getId();
								$lastItem = $item;
							}
						}					
						if ($lastItem){
							$itemm = $quote->getItemById($lastItem->getId());
							if (!$itemm) {
								continue;
							}										
							$item->setBoxId($itemobject['box_id']);
							$item->setBoxName($itemobject['box_name']);
							$item->setBoxType($itemobject['box_type']);
							$item->setBoxProductId($itemobject['box_product_id']);
							$item->save();		
						}						
					$index++;					 
				}
			}
			if($orderData['isboxType']==1){
				foreach ($orderData['items'] as $itemobjects) {
					$index=0;					
					foreach ($itemobjects as $itemobject) {
						$product = $this->_productloader->create()->load($itemobject['product']);
						$object = new \Magento\Framework\DataObject();
						$object->setData($itemobject); 
						try{
								$quote->addProduct($product, $object);
						} catch (\Exception $e) {
							echo $e->getMessage();
							error_log($e->getMessage());					
						}
					// get last quote id and last inserted item id
						$quote->save();
						$lastInsrtquoteId = $quote->getId();	 		
						$items = $quote->getAllVisibleItems();
						$max = 0;
						$lastItem = null;
						$boxitemId =null;
						foreach ($items as $item){
							if ($item->getId() > $max) {
								$max = $item->getId();
								$lastItem = $item;
							}
							if($item->getBoxId()=='1'){
								$boxitemId = $item->getId();
							}
						}					
						if ($lastItem){		
							
							$itemm = $quote->getItemById($lastItem->getId());
							if (!$itemm) {
								continue;
							}
							$item->setBoxId($itemobject['box_id']);
							$item->setBoxName($itemobject['box_name']);
							$item->setBoxType($itemobject['box_type']);
							$item->setBoxProductId($itemobject['box_product_id']);
							$item->save();		
						}	
						
					$index++;	
				  }
				}
				//update quote item id in box item				
				$this->updateBoxItemId($quote->getId());
			}
            $quote->save();
		}
//die('stop');
         //Set Address to quote
            $quote->getBillingAddress()->addData($orderData['address']);
            $quote->getShippingAddress()->addData($orderData['address']);

         // Collect Rates and Set Shipping & Payment Method
            $shippingAddress=$quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('freeshipping_freeshipping'); //shipping method
            $quote->setPaymentMethod('cashondelivery'); //payment method
            $quote->setInventoryProcessed(false); //not effetc inventory       
        
         // Set Sales Order Payment
            $quote->getPayment()->importData(['method' => 'cashondelivery']);        
            $quote->save(); //Now Save quote and your quote is ready
      
         // Collect Totals
            $quote->collectTotals();

         // Create Order From Quote
            $quote = $this->cartRepositoryInterface->get($quote->getId());
            $totals = $this->cartTotalRepository->get($quote->getId());        
            $discountAmount = $totals->getDiscountAmount();        
            $quote->setAppliedRuleIds('');
            $quote->setSubtotalWithDiscount('0.0000');
            $quote->setBaseSubtotalWithDiscount('0.0000');
            $quote->setGrandTotal($quote->getGrandTotal()-$discountAmount);
            $quote->setBaseGrandTotal($quote->getBaseGrandTotal()-$discountAmount);
        
            foreach ($quote->getAllVisibleItems() as $item) {
                $item = $quote->getItemById($item->getId());
                if (!$item) {
                    continue;
                }            
                $item->setAppliedRuleIds('');
                $item->setDiscountPercent('0.00');
                $item->setDiscountAmount('0.00');
                $item->setBaseDiscountAmount('0.00');
                $item->save();
            }
              
            $quote->save();
            $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
            $this->getOrderIncrementId($orderId, $orderData['parent_order_id']);          
            $order = $this->order->load($orderId);      
            $order->setDiscountAmount('0.0000');
            $order->setBaseDiscountAmount('0.0000');
            $order->setGrandTotal($order->getGrandTotal()-$discountAmount);
            $order->setBaseGrandTotal($order->getBaseGrandTotal()-$discountAmount);
            $order->setEmailSent(0);
            $order->setParentOrderId($orderData['parent_order_id']);
            $order->setParentOrderItemId($orderData['parent_order_item_id']);
            $order->setOrderBatchNo($orderData['order_batch_no']);      
        
            $order->save(); 
            $connection = $this->resourceConnection->getConnection(
				\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION
			);        
			$data = ["parent_order_id"=>$orderData['parent_order_id'],"order_batch_no"=>$orderData['order_batch_no']]; // Key_Value Pair			
			$where = ['entity_id = ?' => (int)$orderId];
			$tableName = $connection->getTableName("sales_order_grid");
			$connection->update($tableName, $data, $where);			
			
			if($orderData['isboxType']==1){				
				//update order item id in box item				
				$this->updateOrderBoxItemId($orderId);					
			}		
        } catch (\Exception $e) {
            error_log($e->getMessage());
            die;
        }
        
        
        if ($order->getEntityId()) {
            //$result['order_id']= $order->getRealOrderId();
            $result=['order_id'=> $order->getRealOrderId(),'error'=>0,'msg'=>'Sucess'];
        } else {
            $result=['order_id'=>0,'error'=>1,'msg'=>'something went wrong!'];
        }
        return $result;
    }
    
    public function updateQuoteItem($itemData)
    {
        //$itemData = [$itemId => ['qty' => $itemQty]];
        $this->cart->updateItems($itemData)->save();
        return true;
    }
    
    public function updateOrderIncrementId($parentOrderId)
    {
        $oldOrder = $this->order->load($parentOrderId);
        
        $originalId = $oldOrder->getOriginalIncrementId();
        if (!$originalId) {
            $originalId = $oldOrder->getIncrementId();
        }
            
        $oldOrder->setEditIncrement($oldOrder->getEditIncrement() + 1);
        $oldOrder->save();
        return $originalId;
    }
    
    public function getOrderEditIncrementId($parentOrderId)
    {
        $oldOrder = $this->order->load($parentOrderId);
        
        $originalId = $oldOrder->getEditIncrement();
        if (!$originalId) {
            $originalId = $oldOrder->getEditIncrement();
        }
        
        return $originalId;
    }
    
    public function getOrderIncrementId($OrderId, $parentorderid)
    {
        $oldincrementid = $this->updateOrderIncrementId($parentorderid);
        $oldEditIncrement = $this->getOrderEditIncrementId($parentorderid);
        $order = $this->order->load($OrderId);
        
        $originalId = $order->getOriginalIncrementId();
        if (!$originalId) {
            $originalId = $order->getIncrementId();
        }
        
        $order->setIncrementId($oldincrementid . '#' . ($oldEditIncrement + 1));
        $order->setOriginalIncrementId($originalId);
        $order->setRelationParentId($oldincrementid);
        $order->setRelationParentRealId($parentorderid);
        $order->setEditIncrement($order->getEditIncrement() + 1);
        $order->save();
        return true;
    }
    
    public function getBoxItemId($quoteId){
		
		$quote = $this->quoteFactory->create()->load($quoteId);
		$loaditems = $quote->getAllVisibleItems();
		foreach ($loaditems as $itemms){
			if($itemms->getBoxId()==1){
				return $itemms->getId();
			}
		}
		return this;		
	}
	
	public function updateBoxItemId($quoteId){
		$boxitemId ='';
		$boxitemId = $this->getBoxItemId($quoteId);
		
		$quote = $this->cartRepositoryInterface->get($quoteId);	
		foreach ($quote->getAllVisibleItems() as $item) {
			$item = $quote->getItemById($item->getId());
			if (!$item) {
				continue;
			}

			if($item->getBoxId()=='0'){						
						
				$connection = $this->resourceConnection->getConnection(
					\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION
				);        
				$data = ["box_item_id"=>$boxitemId]; // Key_Value Pair				
				$where = ['item_id = ?' => (int)$item->getId()];
				$tableName = $connection->getTableName("quote_item");			
				$connection->update($tableName, $data, $where);				
			}			
		}
		  
		$quote->save();
		return true;
     }
     
     public function getOrderBoxItemId($orderId){
		
		$order = $this->orderRepository->get($orderId);		
		$loaditems = $order->getAllVisibleItems();
		foreach ($loaditems as $itemms){
			if($itemms->getBoxId()==1){
				return $itemms->getId();
			}
		}
		return this;		
	}
	
	public function updateOrderBoxItemId($orderId){
		
		$boxitemId ='';
		$boxitemId = $this->getOrderBoxItemId($orderId);	
		$order = $this->orderRepository->get($orderId);
		
		foreach ($order->getAllVisibleItems() as $item) {		
			
			if($item->getBoxId()==0){	
				$connectionn = $this->resourceConnection->getConnection(
					\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION
				);        
				$data = ["box_item_id"=>$boxitemId]; // Key_Value Pair				
				$where = ['item_id = ?' => (int)$item->getId()];
				$tableName = $connectionn->getTableName("sales_order_item");			
				$connectionn->update($tableName, $data, $where);
			}
			
		}
		//$order->save();
		return true;
     }
}
