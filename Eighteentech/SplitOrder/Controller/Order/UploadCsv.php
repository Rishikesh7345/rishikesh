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

namespace Eighteentech\SplitOrder\Controller\Order;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Eighteentech\SplitOrder\Model\Import\CustomImport\RowValidatorInterface as ValidatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Customer jbaccount controller
 */
class UploadCsv extends \Magento\Framework\App\Action\Action
{
	const ID = 'entity_id';
	const CUSTOMEREMAIL = 'email';
	const CUSTOMERFIRSTNAME = 'firstname';
	const CUSTOMERLASTNAME = 'lastname';    
	const ADDRESS = 'address';
	const CITY = 'city';
	const STATE = 'state';
	const PINCODE = 'pincode';
	const TELEPHONE = 'telephone';  
    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
    ValidatorInterface::ERROR_MESSAGE_IS_EMPTY => 'Message is empty',
    ];
     protected $_permanentAttributes = [self::ID];
    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;
    protected $resultPageFactory;
    protected $jsonHelper;
    protected $_fileUploaderFactory;
    protected $csvProcessor;
    protected $dataHelper;
    protected $formKey;
    protected $orderItemRepository;
    private $collectionFactory;
    protected $order;
    protected $_orderCollectionFactory;
    /** @var RegionCollection  */
    private $regionCollection;
    /**
     * Valid column names
     *
     * @array
     */
    protected $validColumnNames = [
		self::CUSTOMEREMAIL,
		self::CUSTOMERFIRSTNAME,
		self::CUSTOMERLASTNAME,
		self::ADDRESS,
		self::CITY,
		self::STATE,
		self::PINCODE,
		self::TELEPHONE,    
    ];
    protected $_validators = [];
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Customer\Model\SessionFactory $customerFactory,
        \Eighteentech\SplitOrder\Helper\Data $dataHelper,
        OrderItemRepositoryInterface $orderItemRepository,
        FormKey $formKey,
        CollectionFactory $collectionFactory,
        RegionCollection $regionCollection,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        Order $order,
        OrderCollectionFactory $orderCollectionFactory,
        \Magento\Framework\Filesystem\Driver\File $file,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $this->csvProcessor = $csvProcessor;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->customerFactory = $customerFactory;
        $this->dataHelper = $dataHelper;
        $this->formKey = $formKey;
        $this->orderItemRepository = $orderItemRepository;
        $this->collectionFactory = $collectionFactory;
        $this->regionCollection = $regionCollection;
        $this->itemFactory = $itemFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->order = $order;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->file = $file;
        $this->errorAggregator = $errorAggregator;
        parent::__construct($context);
    }    

    /**
     * execute
     */
    public function execute()
    {
        
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $data = $this->getRequest()->getPostValue();
        $totalcsvRecord = 0;
        $OrderItemQty = 0;
        $items =[];
        $itemId = $data['item_id'] ?? null;
        $orderId = $data['order_id'] ?? null;
        
     // echo "<pre>";
     //  print_r($data);
     //   exit;
        
        $arrFormInputKeys = array("email", "firstname", "lastname", "telephone","city","region_id","postcode","street");
        
        if($data['method_type']=="formfield"){
			
			$addressarr=[];			
			foreach ($data as $key => $dataRows) {		
				if(in_array($key,$arrFormInputKeys)){				
					foreach ($dataRows as $rowIndex => $dataRow) {
						if($key!=='street'){

							$addressarr[$rowIndex][$key]=$dataRow;
						}
					}				
				}			
			}
			$streetArray = $data['street'];		
			//echo "<pre>";
       //print_r($addressarr);	
       //print_r($streetArray);	
					foreach ($addressarr as $rowIndex => $dataRow) {
							for ($i=0; $i<2; $i++) {
								if(isset($addressarr[$rowIndex]['street']))								
									$addressarr[$rowIndex]['street'] .= "<br>".$streetArray[$i][$rowIndex];
								else
									$addressarr[$rowIndex]['street'] = $streetArray[$i][$rowIndex];
							
						}
					}
	

			unset($addressarr[-1]);
			//print_r($addressarr);
			//die;
			$totalcsvRecord =0;
			$totalcsvRecord = (int) $this->getTotalnoRecordInFormArr($addressarr);
			$OrderItemQty = (int) $this->getOrderItemQty($itemId);	
			
			if($totalcsvRecord > $OrderItemQty){
				$itemId = $data['item_id'] ?? null;
				$orderId = $data['order_id'] ?? null;
				$argument = ['order_id' => $orderId, 'item_id' => $itemId];	
				$argument=[];			
				$resultRedirect = $this->resultRedirectFactory->create();				
				$this->messageManager->addWarningMessage(__('Form Data is greater then Ordered qty.'));
				//$resultRedirect->setPath('salesorder/order/split/', $argument);
				$resultRedirect->setPath('salesorder/order/split?order_id='.$orderId.'&item_id='.$itemId, $argument);
				return $resultRedirect;
			}
			
			if (isset($addressarr) && count($addressarr)>0) {
				
				$this->creatOrderDataFormArr($addressarr, $data, $totalcsvRecord, $OrderItemQty);
				
			} else {
				
				$argument = ['order_id' => $orderId, 'item_id' => $itemId];	
				$argument =[];			
				$resultRedirect = $this->resultRedirectFactory->create();
				$this->messageManager->addWarningMessage(__('Address is blank.'));				
				$resultRedirect->setPath('salesorder/order/split?order_id='.$orderId.'&item_id='.$itemId, $argument);
				return $resultRedirect;
			}			
		}
		if($data['method_type']=="csv"){
			$totalcsvRecord = 0;
			$OrderItemQty =0;
			$totalcsvRecord = (int) $this->getTotalnoRecordInCSV();
			$OrderItemQty = (int) $this->getOrderItemQty($itemId);		
        
        if (!isset($data['item_id']) || !isset($data['order_id']) || !isset($_FILES['customer_data_file']['name'])) {
            $argument = [];
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account', $argument);           
            return $resultRedirect;
        }
        
        try {			
			
			if(count($this->getCsvDataValidation())!=0){
				$itemId = $data['item_id'] ?? null;
				$orderId = $data['order_id'] ?? null;
				$argument = ['order_id' => $orderId, 'item_id' => $itemId];	
				$argument=[];			
				$resultRedirect = $this->resultRedirectFactory->create();
				//$resultRedirect->setPath('salesorder/order/split/', $argument);
				$resultRedirect->setPath('salesorder/order/split?order_id='.$orderId.'&item_id='.$itemId, $argument);
				return $resultRedirect;
			}
			
            //var_dump($this->getCsvDataValidation());
           // echo "<br/>";
            //die('eeeeeeeee');
           // $itemId = $data['item_id'] ?? null;
            //$orderId = $data['order_id'] ?? null;
            
            if ($totalcsvRecord <= $OrderItemQty) {
				$itemincrementno =0;
                $items = $this->getOrderItem($itemId);
                $itemincrementno = $this->getOrderItemBatchIncrementId($itemId);
                if($itemincrementno==0 || $itemincrementno==null || $itemincrementno==""){
					$itemincrementno = 1;
				}
                $isboxType = $this->getOrderItemInbox($itemId);
                $row = 0;
                $csvpointer = 1;
                $orderData =[];
                
                if (isset($_FILES['customer_data_file']['name'])) {
                    $tempFile  = $_FILES['customer_data_file']['tmp_name'];
                    $invalidCustomer='';
                    $originalString ='';
                    $importCustomerRawData = $this->csvProcessor->getData($tempFile);
                    //echo "<br/>no of record: ".$noOfLines = count($importCustomerRawData);
                    foreach ($importCustomerRawData as $rowIndex => $dataRow) {
                        $row++;
                        if ($row <= $csvpointer) {
                            continue;
                        }
                        //print_r($dataRow);
                        $originalString = $dataRow[3] ?? null;
                        $replaceSymbols = [',',' ', '_', '.', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '=', '+', '?', '/', '<', '>', '|', '`', '~'];
                        $addressstring = str_replace($replaceSymbols, ' ', trim($originalString));
                        $addressstring = str_replace('  ', ' ', trim($addressstring));
     
                        $currency_id = 'INR' ?? null;
                        $email = $dataRow[0] ?? null;
                        $firstname = $dataRow[1] ?? null;
                        $lastname = $dataRow[2] ?? null;
                        $street = $addressstring ?? null;
                        $city = $dataRow[4] ?? null;
                        $region = $dataRow[5] ?? null;
                        $postcode = $dataRow[6] ?? null;
                        $telephone = $dataRow[7] ?? null;
                        $regionCodearr = $this->getRegionCode($region);
                        $regionCode = $regionCodearr['region_id'] ?? null;
                        $country_id = 'IN';
                        /*if($countrycode==$regionCodearr['country_id']){
                        $country_id = $countrycode;
                        } else {
                        $country_id = $regionCodearr['country_id'] ?? null;
                        }*/
						$invalidcustomer = 0;
						$invalidcustomer = $this->getCsvCustomerValidate($email, $orderId, $itemId);
                        if ($invalidcustomer > 0) {                           
                            $invalidCustomer .= $email.", ";                        
                             continue;
                        }            
                    
                        $orderData =[
                        'parent_order_id'  => $data['order_id'] ?? null,
                        'parent_order_item_id'  => $data['item_id'] ?? null,
                        'currency_id'  => $currency_id,
                        'email'        => $email,
                        'order_batch_no'=> $itemincrementno,
                        'isboxType'=> $isboxType,
                        'address' =>[
                            'firstname'=> $firstname,
                            'lastname' => $lastname,
                            'prefix' => '',
                            'suffix' => '',
                            'street' => $street,
                            'city' => $city,
                            'country_id' => $country_id,
                            'region' => $region,
                            'region_id' => $regionCode,
                            'postcode' => $postcode,
                            'telephone' => $telephone,
                            'fax' => '',
                            'save_in_address_book' => 1
                        ],
                        'items'=>[$items],
                    
                        ];
                    
                       // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/updateOrderItemData.log');
                      //  $logger = new \Zend_Log();
                       // $logger->addWriter($writer);
                      //  $logger->info('before order item update');
                       // $logger->info($orderId." == ".$itemId." == ".$OrderItemQty);
                    
                                        
                        $orderResult =[];
                        $orderResult = $this->dataHelper->createMageOrder($orderData);
                        if ($orderResult['order_id']!=0 && $orderResult['error']==0) {
							$this->updateOrderItemBatchIncrement($itemId);
                            $this->updateOrderItemData($orderId, $itemId, $OrderItemQty); //update item data
                        }
                    }
                    
                    //die('stop');
                    $this->messageManager->addSuccessMessage(__('csv order created successfully.'));
                    $invalidCustomer = substr($invalidCustomer,0.-1);
                    if (isset($invalidCustomer) && $invalidCustomer!=0) {
                        $invalidCustomer = substr($invalidCustomer, 0, -2);
                        $this->messageManager->addErrorMessage(__('Something went wrong! Email Ids have already existed in this order.'));
                        $this->messageManager->addErrorMessage(__($invalidCustomer));
                    }
                }
            
            } else {
                //echo "not creat orderrrr";
                $this->messageManager->addWarningMessage(__('CSV Data is greater then Ordered qty.'));
            }
        } catch (\Exception $e) {
            //error_log($e->getMessage());
            $this->messageManager->addErrorMessage(__($e->getMessage().'Something went wrong!'));
            //die;
        }
        
	}
        
        $argument = ['order_id' => $orderId, 'item_id' => $itemId];
        $argument=[];
        $resultRedirect = $this->resultRedirectFactory->create();
        //$resultRedirect->setPath('salesorder/order/split', $argument);
        $resultRedirect->setPath('salesorder/order/split?order_id='.$orderId.'&item_id='.$itemId, $argument);
        return $resultRedirect;
    }
    
    /* get order Item collection */
    public function getOrderItem($itemId)
    {
        $items =[];
        $itemsarr=[];
        $itemCollection = $this->orderItemRepository->get($itemId); 
		$boxid = '';
		$boxname = '';
		$boxtype = '';
		$boxproductid = '';
		$productoption ='';      
        $subitemload ='';
        $quoteitemId ='';
        $qty = 1;
        
        if($itemCollection['box_id']==1)
        {			
			$orderId = $itemCollection['order_id'];
			$quoteitemId = $itemCollection['quote_item_id'];
			$productid = $itemCollection['product_options']['info_buyRequest']['product'];
			$order = $this->itemFactory->create()->getCollection()
				->addFieldToFilter('order_id', $orderId)
				->addFieldToFilter('box_product_id', $productid)
				->addFieldToFilter('box_item_id', $quoteitemId)
				->addFieldToFilter('box_type', 'yes');
				
			$order->getSelect()->orwhere('item_id = ? ', $itemId);
			
			//echo $order->getSelect(); die;
			$i=0;
            foreach ($order as $items) {
				$options = [];
				$boxid = $items['box_id'];
				$boxname = $items['box_name'];
				$boxtype =$items['box_type'];
				$boxproductid = $items['box_product_id'];
				if ($items['product_type']=="configurable") {					
					$options = $items['product_options']['info_buyRequest']['super_attribute'];				
					$qty = 1;
					$productid = $items['product_options']['info_buyRequest']['product'];
					$parentid = $items['parent_item_id'];
					$subitemload = $this->itemFactory->create()->getCollection()
					->addFieldToFilter('order_id', $orderId)
					->addFieldToFilter('parent_item_id', $itemId);
				
					foreach ($subitemload as $subitemloaditems) {
						 $productoption = $subitemloaditems['product_options'];	
						// print_r(array_keys($productoption['info_buyRequest'], "qty"));
						if($productoption['info_buyRequest']['qty']){
							$productoption['info_buyRequest']['qty']=$qty;
						}						
					}								
					
				} else {
					$options = [];				
					$qty = $qty;
					$productid = $items['product_options']['info_buyRequest']['product'];
				}  
				$itemsarr[] = ['form_key' => $this->formKey->getFormKey(),'product'=>$productid, 'qty'=> $qty,'super_attribute' => $options,'item'=>$productid,'selected_configurable_option'=>'','related_product'=>'','box_id'=>$boxid,'box_name'=>$boxname,'box_type'=>$boxtype,'box_product_id'=>$boxproductid,'productoption'=>$productoption,'catitemid'=>$items['item_id']]; 
				$i++;            
			}
			 
			return $itemsarr;
			
		} else {  
        
			//echo "<pre>";print_r($itemCollection->debug());
			// die('sddsdssd');
			$options =[];
			if ($itemCollection['product_type']=="configurable") {
				$options = $itemCollection['product_options']['info_buyRequest']['super_attribute'];
			  //$qty = $itemCollection['product_options']['info_buyRequest']['qty'];
				$qty = $qty;
				$productid = $itemCollection['product_options']['info_buyRequest']['product'];
			} else {
				$options = [];
				//$qty = $itemCollection['product_options']['info_buyRequest']['qty'];
				$qty = $qty;
				$productid = $itemCollection['product_options']['info_buyRequest']['product'];
			}
			 $items=['form_key' => $this->formKey->getFormKey(),'product'=>$productid, 'qty'=> $qty,'super_attribute' => $options,'item'=>$productid,'selected_configurable_option'=>'','related_product'=>'','box_id'=>$boxid,'box_name'=>$boxname,'box_type'=>$boxtype,'box_product_id'=>$boxproductid,'productoption'=>$productoption,'catitemid'=>$itemId];
						
			return $items;
        }
    }
     
    public function getTotalnoRecordInCSV()
    {
        if (isset($_FILES['customer_data_file']['name'])) {
            $tempFile  = $_FILES['customer_data_file']['tmp_name'];
            $importCustomerRawData = $this->csvProcessor->getData($tempFile);
            return count($importCustomerRawData)-1;
        }
    }
    
    public function getOrderItemQty($itemId)
    {
         $itemCollection = $this->orderItemRepository->get($itemId);
        
        /* if($itemCollection['product_type']=="configurable"){
            return $orderedqty = $itemCollection['qty_ordered'];
         } else {    */
            return $orderedqty = $itemCollection['qty_remaning']?$itemCollection['qty_remaning']:$itemCollection['qty_ordered'];
         //}
    }
    
    public function getRegionCode(string $region): array
    {
        $regionCode=[];
        $regionCode = $this->collectionFactory->create()
            ->addRegionNameFilter($region)
            ->getFirstItem()
            ->toArray();
        return $regionCode;
    } 
    
    /**
     * @param int $id
     * @return string
     */
    private function getRegionNameById(int $id): string
    {
        $region = $this->regionCollection->getItemById($id);

        return $region->getName();
    }
    
    public function updateOrderItemData($orderId, $olditemId, $orderedqty)
    {
        try {
                        
            $order = $this->itemFactory->create()->getCollection()->addFieldToFilter('order_id', $orderId);
            foreach ($order as $items) {
                $itemId =  $items->getItemId();
                $remaningqty=$orderedqty-1;
                if ($itemId===$olditemId) {
                    
                    $order = $this->orderItemRepository->get($itemId);                    
                    $movedqty = 1;
                    if ($order->getQtyRemaning()) {
                        $remaningqty  = $order->getQtyRemaning()-1;
                    }

                    if ($order->getQtyMoved()) {
                        $movedqty = $order->getQtyMoved()+1;
                    } 
                    
                    //$remaningqty = $orderedqty - $movedqty;
                    
                    /*$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/duringupdateOrderItemData.log');
                    $logger = new \Zend_Log();
                    $logger->addWriter($writer);
                    $logger->info('during order item update');
                    $logger->info($orderedqty." == ".$movedqty." == ".$remaningqty."=> ".$order->getQtyRemaning());*/
                                    
                    $order->setQtyMoved($movedqty);
                    $order->setQtyRemaning($remaningqty);                    
                    $order->save(); 
                    
                }
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
        return true;
    }
    
    public function getCsvCustomerValidate($customeremail, $orderId, $itemId)
    {
        
        $collection = $this->_orderCollectionFactory->create()
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('parent_order_id', ['eq' => $orderId])
        ->addAttributeToFilter('parent_order_item_id', ['eq' => $itemId])
        ->addAttributeToFilter('customer_email', ['eq' => $customeremail]);
        
        return $collection->count();
    }
    
    public function getValidColumnNames()
    {
        return $this->validColumnNames;
    }
    
    /**
     * Row validation.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function validateRow(array $rowData)
    {
		$validate = true;
		if ($rowData[0]=="" || $rowData[1]=="" || $rowData[2]=="" || $rowData[3]=="" || $rowData[4]=="" || $rowData[5]=="" || $rowData[6]=="" || $rowData[7]=="") {
			$validate = 0;		
		}		
		return $validate;
    }
    
    public function getCsvDataValidation(){
		
		$row = 0;
		$csvpointer = 1;
		$argument =[];
		if (isset($_FILES['customer_data_file']['name'])) {
			$tempFile  = $_FILES['customer_data_file']['tmp_name'];			
			$importCustomerRawData = $this->csvProcessor->getData($tempFile);
			$argument = [];
			foreach ($importCustomerRawData as $rowIndex => $dataRow) {
				$row++;
				if ($row <= $csvpointer) {
					continue;
				}				
				if (!$this->validateRow($dataRow)) {
					$argument[$row] = ['rowIndex'=>$rowIndex,'message'=>'Row column empty data!'];					
					$this->messageManager->addErrorMessage(__('Something went wrong! In CSV Row column empty data or invalid data in Row No - '.$rowIndex));										
					continue;
				}				
			}
		}
                        
		return $argument;
	}
	
	public function getTotalnoRecordInFormArr($addressarr){
		
		return count($addressarr);
	
	}	
	
	public function creatOrderDataFormArr($importCustomerRawData, $data, $totalcsvRecord, $OrderItemQty)
	{			
        $invalidCustomer='';
		$items =[];		
		$itemId = $data['item_id'] ?? null;
		$orderId = $data['order_id'] ?? null;
		
		try { 
			
		if ($totalcsvRecord <= $OrderItemQty) {
			$itemincrementno = 0;
			$items = $this->getOrderItem($itemId);			
			$itemincrementno = $this->getOrderItemBatchIncrementId($itemId);
			if($itemincrementno==0 || $itemincrementno==null || $itemincrementno==""){
				$itemincrementno = 1;
			}			
			$isboxType = $this->getOrderItemInbox($itemId);
			
			foreach ($importCustomerRawData as $rowIndex => $dataRow) {
				
				//print_r($dataRow);
				//echo $dataRow['email'];
				//echo "<br/>". $dataRow['street'];
				//die;
								
		
				//print_r($dataRow);
				//die('kkkkkkkkkkkkkkkkkkkkkk');
				
				
				$originalString = $dataRow['street'] ?? null;
				$replaceSymbols = [',',' ', '_', '.', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '=', '+', '?', '/', '<', '>', '|', '`', '~'];
				$addressstring = str_replace($replaceSymbols, ' ', trim($originalString));
				$addressstring = str_replace('  ', ' ', trim($addressstring));

				$currency_id = 'INR' ?? null;
				$email = $dataRow['email'] ?? null;
				$firstname = $dataRow['firstname'] ?? null;
				$lastname = $dataRow['lastname'] ?? null;
				$street = $addressstring ?? null;
				$city = $dataRow['city'] ?? null;
				$region = $this->getRegionNameById($dataRow['region_id']) ?? null;
				$postcode = $dataRow['postcode'] ?? null;
				$telephone = $dataRow['telephone'] ?? null;
				$regionCodearr = $this->getRegionCode($region);
				$regionCode = $dataRow['region_id'] ?? null;
				$country_id = 'IN';
				
				/*if($countrycode==$regionCodearr['country_id']){
				$country_id = $countrycode;
				} else {
				$country_id = $regionCodearr['country_id'] ?? null;
				}*/
				$invalidcustomer = 0;
				$invalidcustomer = $this->getCsvCustomerValidate($email, $orderId, $itemId);

				if ($invalidcustomer > 0) {                           
					$invalidcustomer .= $email.", ";                        
					 continue;
				}            

				$orderData =[
				'parent_order_id'  => $orderId ?? null,
				'parent_order_item_id'  => $itemId ?? null,
				'currency_id'  => $currency_id,
				'email'        => $email,
				'order_batch_no'=> $itemincrementno,
				'isboxType'=> $isboxType,
				'address' =>[
					'firstname'=> $firstname,
					'lastname' => $lastname,
					'prefix' => '',
					'suffix' => '',
					'street' => $street,
					'city' => $city,
					'country_id' => $country_id,
					'region' => $region,
					'region_id' => $regionCode,
					'postcode' => $postcode,
					'telephone' => $telephone,
					'fax' => '',
					'save_in_address_book' => 1
				],
				'items'=>[$items],

				];

//echo "<pre>";
//print_r($orderData);
//die;
				//$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/updateOrderItemData.log');
				//$logger = new \Zend_Log();
				//$logger->addWriter($writer);
				//$logger->info('before order item update');
				//$logger->info($orderId." == ".$itemId." == ".$OrderItemQty);
								
				$orderResult =[];
				$orderResult['order_id']=0;
				$orderResult['error']=1;
				$orderResult = $this->dataHelper->createMageOrder($orderData);

				if ($orderResult['order_id']!=0 && $orderResult['error']==0) {
					$this->updateOrderItemBatchIncrement($itemId);
					$this->updateOrderItemData($orderId, $itemId, $OrderItemQty); //update item data
					$this->messageManager->addSuccessMessage(__('Order created successfully.'));
				}
			} 
			
			if (isset($invalidcustomer) && $invalidcustomer!=0) {
				$invalidcustomer = substr($invalidcustomer, 0, -2);
				$this->messageManager->addErrorMessage(__('Something went wrong! Email Ids have already existed in this order.'));
				$this->messageManager->addErrorMessage(__($invalidcustomer));
			}
                    
		} else {
                //echo "not creat orderrrr";
                $this->messageManager->addWarningMessage(__('Form Data is greater then Ordered qty.'));
            }
        } catch (\Exception $e) {
            //error_log($e->getMessage());
            $this->messageManager->addErrorMessage(__($e->getMessage().'Something went wrong!'));
            //die;
        } 
        return true;           
	}
	
	public function getOrderItemBatchIncrementId($olditemId)
    {
        try {			
			
			$order = $this->orderItemRepository->get($olditemId);			
			$orderBatchIncrement=0; 
			$orderBatchIncrement  = $order->getOrderBatchIncrement();	
			//$order->setOrderBatchIncrement($orderBatchIncrement);
            // $order->save(); 		

			return $orderBatchIncrement;
                    
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
        return $orderBatchIncrement;
    }
    
    public function updateOrderItemBatchIncrement($olditemId)
    {
        try {			
			
			$order = $this->orderItemRepository->get($olditemId);			
			$orderBatchIncrement=0; 
			$orderBatchIncrement  = $order->getOrderBatchIncrement()+1;	
			$order->setOrderBatchIncrement($orderBatchIncrement);
            $order->save(); 		

			return $orderBatchIncrement;
                    
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
        return $orderBatchIncrement;
    }
    
    public function getOrderItemInbox($itemId)
    {
         $itemCollection = $this->orderItemRepository->get($itemId); 
         if($itemCollection['box_id']){      
			return $orderediteminbox = $itemCollection['box_id'];
		 } else {
			return $orderediteminbox = 0;
		 }
         
    }
	
}
