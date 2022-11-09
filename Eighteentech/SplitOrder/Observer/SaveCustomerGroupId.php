<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @category Eighteentech
 * @package  Eighteentech_SampleProducts
 *
 */
 
namespace Eighteentech\SplitOrder\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;

class SaveCustomerGroupId implements ObserverInterface
{
    
    public $_customerRepositoryInterface;
    public $_messageManager;
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        ManagerInterface $messageManager,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_messageManager = $messageManager;
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
       //$accountController = $observer->getAccountController();
       //$request = $accountController->getRequest();
       
        $customertype=0;
        $customertype = $this->_request->getParam('customer_type');
       
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/registrationdata.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('text message');
        $logger->info($customertype);

        try {
           
            if ($customertype==1) {
                $group_id = 4;
                $customerId = $observer->getCustomer()->getId();
                $customer = $this->_customerRepositoryInterface->getById($customerId);
                $customer->setGroupId($group_id);
                $this->_customerRepositoryInterface->save($customer);
            }

        } catch (Exception $e) {
            $this->_messageManager->addErrorMessage(__('Something went wrong! Please try again.'));
        }
    }
}
