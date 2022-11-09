<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Action Delete.
 *
 * Deletes item from cart.
 */
class Delete extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
        
    /**
     * Delete shopping cart item action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                $delQuoteItem = $this->cart->getQuote()->getItemById($id);
                $delProId = $delQuoteItem->getItemId();

                $this->cart->removeItem($id);

                // We should set Totals to be recollected once more because of Cart model as usually is loading
                // before action executing and in case when triggerRecollect setted as true recollecting will
                // executed and the flag will be true already.
                $this->cart->getQuote()->setTotalsCollectedFlag(false);
                $this->cart->save();

                $quote = $this->cart->getQuote();
                foreach ($quote->getItems() as $item) {
                    
                    if ($delProId == $item->getBoxItemId()) {
                        $item->setBoxType(null);
                        $item->setBoxItemId(null);
                        $item->setBoxProductId(null);
                        $item->setProductDim(null);
                        //$item->setBoxProdName(null);
                        $item->setEsdcPricing(null);
                        $item->save();
                    }
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t remove the item.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $defaultUrl = $this->_objectManager->create(\Magento\Framework\UrlInterface::class)->getUrl('*/*');

        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
    }
}
