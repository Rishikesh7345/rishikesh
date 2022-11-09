<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Eighteentech\SplitOrder\Controller\AbstractController;

use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\ForwardFactory;

class OrderLoader
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;


    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Registry $registry,
        ForwardFactory $resultForwardFactory
    ) {
        $this->orderFactory = $orderFactory;
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * @param RequestInterface $request
     * @return bool|\Magento\Framework\Controller\Result\Forward|\Magento\Framework\Controller\Result\Redirect
     */
    public function load($orderId)
    {
        $orderId = (int)$orderId;
        if (!$orderId) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        $order = $this->orderFactory->create()->load($orderId);
        $this->registry->register('current_orders', $order);
        return true;
    }
}
