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

namespace Eighteentech\SplitOrder\Block;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Customer\Model\Context;
use Magento\Framework\Data\Form\FormKey;

class OrderGrid extends Template
{
    
    protected $_orderCollectionFactory;
    protected $priceCurrency;
    protected $timezone;
    protected $formKey;
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $orders;

    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;
    /**
     * @var \Magento\Framework\App\Http\Context
     * @since 101.0.0
     */
    protected $httpContext;

    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        FormKey $formKey,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->timezone = $timezone;
        $this->request = $request;
        $this->_orderConfig = $orderConfig;
        $this->httpContext = $httpContext;
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }
    
    public function getOrderCollectiontt()
    {
        $orderid = $this->getOrderId();
        $collection = $this->_orderCollectionFactory->create()
         ->addAttributeToSelect('*')
         ->addAttributeToFilter('parent_order_id', ['eq' => $orderid]);
        return $collection;
    }
    
    public function getCurrencyWithFormat($price)
    {
        return $this->priceCurrency->format($price, true, 2);
    }
    
    public function getFormatDate($date)
    {
        return $dateTimeZone = $this->timezone->date(new \DateTime($date))->format('d/m/Y');
    }
    
    public function getMyCustomMethod()
    {
        return '<b>I Am From MyCustomMethod</b>';
    }
    
    public function getOrderId()
    {		
        return $order_id= $this->request->getParam('order_id');
    }
    
    public function getOrderItemId()
    { 
		 return $this->request->getParam('item_id');		 
    }
    
    public function getViewSubOrders()
    { 
		$orderview=0;
		if($this->request->getParam('view')!=null){
		  $orderview = 1;
		}
		return $orderview;	 
    }
    
    public function getPageId()
    {
        return $this->request->getParam('p')?$this->request->getParam('p'):0;
    }
    
    public function getPageLimit()
    {
        return $this->request->getParam('limit')?$this->request->getParam('limit'):10;
    }
    
    /**
     * Provide order collection factory
     *
     * @return CollectionFactoryInterface
     * @deprecated 100.1.1
     */
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }

    /**
     * Get customer orders
     *
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        //if (!($customerId = $this->_customerSession->getCustomerId())) {
            //return true;
        //}
        
        $orderid = $this->getOrderId();
        if ($orderid) {
            
            $this->orders = $this->getOrderCollectionFactory()->create()->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'status',
                ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
            )->addAttributeToFilter(
                'parent_order_id',
                ['eq' => $orderid]
            )->setOrder(
                'created_at',
                'desc'
            );
            
            $pageId = $this->getPageId();
			$limit = $this->getPageLimit();
			$this->orders->getSelect()->limit($limit, $pageId);
        }
        
        return $this->orders;
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getOrders()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'order_grid.pager'
            )->setCollection(
                $this->getOrders()
            );
            $this->setChild('pager', $pager);
            $this->getOrders()->load();
        }
        return $this;
    }

    /**
     * Get Pager child block output
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }  /**
        * Get customer account URL
        *
        * @return string
        */
    public function getBackUrl()
    {
        return $this->getUrl('sales/order/view', ['order_id' => $this->getOrderId()]);
        //$this->getUrl('customer/account/');
    }

    /**
     * Get message for no orders.
     *
     * @return \Magento\Framework\Phrase
     * @since 102.1.0
     */
    public function getEmptyOrdersMessage()
    {
        return __('You have placed no orders.');
    }
    
    /**
     * Return back title for logged in and guest users
     *
     * @return \Magento\Framework\Phrase
     */
    public function getBackTitle()
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return __('Back to My Orders');
        }
        return __('View Another Order');
    }
    
    public function getParentOrderIncrementId($orderId)
    {
        $collections = $this->_orderCollectionFactory->create()
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('entity_id', ['eq' => $orderId]);
        foreach ($collections as $collection) {
            return $collection->getIncrementId();
        }
    }
    
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
