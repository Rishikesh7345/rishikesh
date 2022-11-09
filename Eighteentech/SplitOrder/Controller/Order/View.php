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

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\View\Result\PageFactory;

class View extends \Magento\Framework\App\Action\Action implements OrderInterface, HttpGetActionInterface
{
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\SessionFactory $customerFactory,
        \Magento\Framework\App\Request\Http $request,
        \Eighteentech\SplitOrder\Controller\AbstractController\OrderLoader $orderLoader
    ) {
        parent::__construct($context);
        $this->customerFactory = $customerFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderLoader = $orderLoader;
    }
   
    public function execute()
    {
       
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Orders'));
        $customer  = $this->customerFactory->create();
        
        if (!$customer->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }
        
        if ($customer->isLoggedIn()) {
              $order_id = $this->request->getParam('order_id');
              $order = $this->orderLoader->load($order_id);
        }
        return $resultPage;
    }
}
