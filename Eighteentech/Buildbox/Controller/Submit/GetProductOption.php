<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Controller\Submit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Session;

/**
 * build box product option controller.
 */
class GetProductOption extends Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\UrlFactory
     */
    protected $urlFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    
    /**
     * @var ProductFactory
     */
    protected $_productloader;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var productCollectionFactory
     */
     protected $productCollectionFactory;

    /**
     * @var Option $_imageBuilder
     */
    protected $_imageBuilder;
    
    /**
     * @var Option $_customOptions
     */
    protected $_customOptions;
    
    /**
     * @var Option $option
     */
    protected $option;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RawFactory $resultRawFactory
     * @param ProductFactory $_productloader
     * @param UrlFactory $urlFactory
     * @param Session $session
     * @param StoreManagerInterface $storeManager
     * @param Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param CollectionFactory $productCollectionFactory
     * @param ImageBuilder $_imageBuilder
     * @param Option $customOptions
     * @param Option $option
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */

    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        ProductFactory $_productloader,
        UrlFactory $urlFactory,
        Session $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder,
        \Magento\Catalog\Model\Product\Option $customOptions,
        \Magento\Catalog\Model\Product\Option $option,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
            $this->resultPageFactory    = $resultPageFactory;
            $this->resultRawFactory     = $resultRawFactory;
            $this->_productloader       = $_productloader;
            $this->urlModel             = $urlFactory->create();
            $this->_session = $session;
            $this->storeManager = $storeManager;
            $this->productRepository   = $productRepository;
            $this->productCollectionFactory = $productCollectionFactory;
            $this->_imageBuilder = $_imageBuilder;
            $this->_customOptions = $customOptions;
            $this->option = $option;
            $this->priceCurrency = $priceCurrency;
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

        /**
         * @var $html
         * store html formate
         */
        $html = '';

        /**
         * @var $optionId
         * get pruduct option id
         */
        $optionId = '';

        /**
         * @var $filename
         * get option file name
         */
        $filename='';
        /**
         * @var $addonsPrice
         * Get Addons Price in formate
         */
        $addonsPrice = '';
        $product = $this->_productloader->create()->load($post['childOpId']);
        $customOptions = $this->_customOptions->getProductOptionCollection($product);
        $html .= '
        <div class="addons-container">
            <div class="addons-section"> ';
        foreach ($customOptions as $option) {
            $addonsPrice = $this->priceCurrency->convertAndFormat($option->getPrice(), 2);
            $filename = "options_".$option->getOptionId()."_file";
            $html .= ' 
                <div class="additional">
                    <div class="optionTitle">
                        <h3>'.$option->getTitle().'</h3>
                        <h4>Addons price:'.$addonsPrice.'</h4>
                    </div>
                    <div class="optionName"> 
                        <div class="optionFeild">
                            <input type="'.$option->getType().'" name="'.$filename.'"/>
                            <input type="radio" name="radioSelect" class="option-field-'.$option->getId().'" 
                            optionIdVal="'.$option->getId().'"/>
                            <span id="getFileName"></span>
                            <input type="hidden" name="optionProId" value="'.$post["childOpId"].'"/>
                            <input type="hidden" name="optionId[]" value="'.$option->getOptionId().'"/>
                        </div>
                        <div class="fileExt">File Extension is Require('.$option->getFileExtension().')</div>
                    </div>
                    
                </div>';
        }
            $html .= '
            </div>
        </div>';
        $result->setContents($html);
        return $result;
    }
}
