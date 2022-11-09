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
use Eighteentech\SplitOrder\Helper\Data as HelperData;

class Dashboard extends \Magento\Framework\App\Action\Action implements OrderInterface, HttpGetActionInterface
{
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\SessionFactory $customerFactory,
        \Magento\Framework\App\Request\Http $request,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->customerFactory = $customerFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->helperData = $helperData;
    }

    public function execute()
    {
       
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Retail Orders'));
        $customer  = $this->customerFactory->create();
        $configGroupId = $this->helperData->getConfigGroupId();
        $groupId = $customer->getCustomer()->getGroupId();
       
        if (!$customer->isLoggedIn() || $groupId!=$configGroupId) {
            return $this->_redirect('customer/account/login');
        }
        //echo $customer->getCustomer()->getGroupId(); die;
        //&& $customer->getCustomer()->getId()!=8       
        
        if ($customer->isLoggedIn() && $groupId==$configGroupId) {
          //return $this->_redirect('customer/account/');
            $order_id= $this->request->getParam('order_id');
            $item_id= $this->request->getParam('item_id');
            return $resultPage;
        }
    }
}
