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
use Magento\Customer\Model\Context;

class View extends Template
{
    
    protected $_orderCollectionFactory;
    protected $priceCurrency;
    protected $timezone;
    protected $_paymentHelper;
    protected $httpContext;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Registry $registry,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->timezone = $timezone;
        $this->_coreRegistry = $registry;
        $this->_paymentHelper = $paymentHelper;
        $this->order = $orderFactory;
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
    }
    
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getRealOrderId()));
        //$infoBlock = $this->_paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        //$this->setChild('payment_info', $infoBlock);
    }
    
    public function getOrderCollection()
    {
        //$data = $this->getRequest()->getPostValue();
        //$orderId = $data['order_id'] ?? 2;
        $orderId = $this->getOrderId();
        $collection = $this->_orderCollectionFactory->create()
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('parent_order_id', ['eq' => $orderId]);
        
        //echo $collection->getSelect();
     
        return $collection;
    }
    
    public function getCurrencyWithFormat($price)
    {
        return $this->priceCurrency->format($price, true, 2);
    }
    
    public function getFormatDate($date)
    {
        return $dateTimeZone = $this->timezone->date(new \DateTime($date))->format('d/m/y');
    }
    
    public function getOrder()
    {
     
         /* if aleady exits then remove and set new once */
        if ($order = $this->_coreRegistry->registry('current_orders')) {
            return $order;
        }
         
        if ($orderId = $this->getRequest()->getParam('order_id')) {
             $order = $this->order->create()->load($orderId);
               return $order;
        }
    }
    public function getBackUrl()
    {
        return $this->getUrl('salesorder/order/split', ['order_id' => $this->getOrderParentId(),'item_id' => $this->getOrderItemId()]);
    }
    
    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }
    
    public function getBackTitle()
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return __('Back to My Orders');
        }
        //return __('View Another Order');
    }

    /**
     * @param object $order
     * @return string
     */
    public function getInvoiceUrl($order)
    {
        return $this->getUrl('*/*/invoice', ['order_id' => $order->getId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getShipmentUrl($order)
    {
        return $this->getUrl('*/*/shipment', ['order_id' => $order->getId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getCreditmemoUrl($order)
    {
        return $this->getUrl('*/*/creditmemo', ['order_id' => $order->getId()]);
    }
    
    public function getMyCustomMethod()
    {
        return '<b>I Am From MyCustomMethod</b>';
    }
    
    public function getOrderId()
    {
        return $order_id= $this->getRequest()->getParam('order_id');
    }
    
    public function getOrderItemId()
    {
        return $item_id= $this->getRequest()->getParam('item_id');
    }
    
    public function getOrderParentId()
    {
        return $item_id= $this->getRequest()->getParam('porder_id');
    }
}
