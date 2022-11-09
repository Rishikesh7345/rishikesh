<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Controller\Submit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class GetBoxQty extends Action
{
    /**
     * @var \Magento\Framework\use Magento\Framework\App\ObjectManager;View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     */
    protected $jsonResultFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;
    
    /**
     * Constructor.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */

    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {

        $this->resultRawFactory     = $resultRawFactory;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->priceCurrency = $priceCurrency;
        $this->_objectManager = $objectmanager;
        parent::__construct($context);
    }

    /**
     * Execute the function for get Item color
     *
     * @return array
     */
    public function execute()
    {
        $result = $this->resultRawFactory->create();
        $post = $this->getRequest()->getPostValue();

        $prodQty = $post['producQty'];

        $product_Id = $post['productId'];
        $stockInfo = $this->_objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
        ->getStockItem($product_Id);
        $stockqty = (int)$stockInfo->getQty();

        /**
         * @var $html
         * Get all Html formate
         */
        $html = '';

        /**
         * @var $arrQty
         * store product qty array
         */
        $arrQty = [];
        $reqQty = $post['boxQty'] -  $prodQty;
        if ($post['boxQty'] <=  $prodQty) {
            $totPrice = $post['boxQty'] * $post['boxPrice'];
            $formateprice = $this->priceCurrency->convertAndFormat($totPrice, 2);
            $html .= '<span>'.$formateprice.'</span><input type="hidden" name="productQty" value="'.$prodQty.'"/>';
            $result->setContents($html);
        } else {
            $arrQty = [0,$reqQty];
            $result = $this->jsonResultFactory->create();
            $result->setData($arrQty);
        }
        return $result;
    }
}
