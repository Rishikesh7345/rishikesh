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

class Proid extends Action
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
      * @var \Magento\Framework\ObjectManagerInterface
      */
    private $objectManager;

    protected $_stockItemRepository;

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
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
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
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
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
            $this->objectManager = $objectmanager;
            $this->_stockItemRepository = $stockItemRepository;
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
      
        $product = $this->_productloader->create()->load($post['boxId']);
        $StockState = $this->objectManager->get(\Magento\CatalogInventory\Api\StockStateInterface::class);
        $inputQty = $StockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
            
        /**
         * @var $parentProdId
         * store product parent id
         */
        $parentProdId = $post['boxParentId'];
        $html='';
        $product = $this->_productloader->create()->load($post['boxId']);
        $parentProduct = $this->_productloader->create()->load($parentProdId);
        $store = $this->storeManager->getStore();
        $productImageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::
        URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
        $totDim = ($product->getWidth() * $product->getHeight() * $product->getLenght())/1000;
        $product->getBoxweight();
        $finalPrice = $this->priceCurrency->convertAndFormat($product->getFinalPrice(), 2);
        $html .= '
        <div class="product-cart-box">                 
               <div class="product-details">
                    <div class="product-details-img">
                        <img src="'.$productImageUrl.'"/>
                    </div>
                </div>
                <div class="product-details">
                    <div class="product-details-name">
                        <h2>'.$parentProduct->getName().'</h2>
                        <input type="hidden" name="parentProductId" value="'.$parentProdId.'"/>
                    </div>
                    <div class="product-details-price"
                        <p>Par Box Price: '.$finalPrice.'</p>
                    </div>
                    <div class="product-production-time" style="display:none;">
                        <p>Box production time: '.$product->getProductionDay().'day</p>
                        <input type="hidden" name="production" value="'.$product->getProductionDay().'" 
                        inputProductQty[]="'. $inputQty.'"  id="inputProductQty"/>
                    </div>
                </div>                    
                <div class="color-section">';
        if ($parentProduct->getTypeId() == 'configurable') {
            $storeId = $this->storeManager->getStore()->getId();
            $productTypeInstance = $parentProduct->getTypeInstance();
            $productTypeInstance->setStoreFilter($storeId, $parentProduct);
            $usedProducts = $productTypeInstance->getUsedProducts($parentProduct);
            $productAttributeOptions = $parentProduct->getTypeInstance(true)
            ->getConfigurableAttributesAsArray($product);
            
            /**
             * @var $optionValue
             * store product options value
             */
            $optionValue = [];

            /**
             * @var $proChildId
             * store product child id
             */
            $proChildId = [];

            /**
             * @var $proParentId
             * store product parent id
             */
            $proParentId = [];
            $html .= '<div class="conf-child-att">';
            $colorList = [];
            $size=0;
            $sizeOp = 0;
            foreach ($usedProducts as $child) {
                if ($child->getId() == $post['boxId']) {
                 //echo $child->getId();
                    $sizeOp = $this->getOptionLabelByValue('boxweight', $child->getBoxweight());
                }
                $size = $this->getOptionLabelByValue('boxweight', $child->getBoxweight());
                if ($sizeOp == $size) {
                    $proChildId[] = $child->getId();
                    $colorList[] = $this->getOptionLabelByValue('boxcolor', $child->getBoxcolor());
                }
                $proParentId[] = $product->getId();
                $html .='<input type="hidden" name="parent_product_id" value="'. $product->getId() .'"/>
                        <input type="hidden" name="box_price" id="box_price"
                            value="'. $product->getFinalPrice() .'"/>
                        <input type="hidden" name="boxchildId" value="'.$child->getId().'" />
                        <input type="hidden" name="Item_id" 
                        value="'. $product->getParentItemId() .'"/>';
            }
            $colorNum = count($colorList);
            if ($colorNum != 1) {
                $html .='
                    <div class="color-list-section">
                        <h2>Select Color</h2>
                        <div class="color-inner-list">';
                for ($i=0; $i< $colorNum; $i++) {
                    $html .='
                        <label class="container1">
                            <input type="radio" name="child_product" 
                            value="'.  $proChildId[$i] .'" class="childProOption">
                            
                            <input type="hidden" name="option_id" value="'. $child->getBoxcolor() .'"/>
                            <span class="checkmark" style="background-color:'.$colorList[$i].'"></span>
                        </label>';
                }
                $html .= '</div></div>';
            }
            $html .= '</div></div>';
        }
        
        if ($colorNum == 1) {

            /**
             * @var $optionId
             * store product options id
             */
            $optionId = '';

            /**
             * @var $filename
             * store product options filename
             */
            $filename='';

            /**
             * @var $addonsPrice
             * Get Addons Price in formate
             */
            $addonsPrice = '';
            $product = $this->_productloader->create()->load($post['boxId']);
            $customOptions = $this->_customOptions->getProductOptionCollection($product);
            
            $html .= '
            <div class="addons-container">
                <div class="addons-section"> ';
            foreach ($customOptions as $option) {
                $filename = "options_".$option->getOptionId()."_file";
                $addonsPrice = $this->priceCurrency->convertAndFormat($option->getPrice(), 2);
                $html .= ' 
                    <div class="additional">
                        <div class="optionTitle">
                            <h3>'.$option->getTitle().'</h3>
                            <h4>Addons price:'.$addonsPrice.'</h4>
                        </div>                  
                        <div class="optionName">
                            <div class="optionFeild">
                                <input type="'.$option->getType().'" name="'.$filename.'" 
                                optionId="'.$option->getId().'"/>
                                <input type="radio" name="radioSelect" class="option-field-'.$option->getId().'" 
                                optionIdVal="'.$option->getId().'"/>                              
                                
                                <input type="hidden" name="optionProId" value="'.$post['boxId'].'"/>
                                <input type="hidden" name="optionId[]" value="'.$option->getOptionId().'"/>
                                <span class="getImgName"></span>                               
                            </div>
                            <div class="fileExt">File Extension is Require('.$option->getFileExtension().')</div>
                        </div>
                    </div>';
            }
            $html .= ' </div>
            <input type="hidden" name="selected_option_id" id="option_id"/>
            </div>';
        }
        $html .= '</div>';
        $result->setContents($html);
        return $result;
    }

    /**
     * Get Option Label By Value
     *
     * @param string $attributeCode
     * @param string $optionId
     * @return array
     */
    public function getOptionLabelByValue($attributeCode, $optionId)
    {
        $product = $this->_productloader->create();
        $isAttributeExist = $product->getResource()->getAttribute($attributeCode);
        $optionText = '';
        if ($isAttributeExist && $isAttributeExist->usesSource()) {
            $optionText = $isAttributeExist->getSource()->getOptionText($optionId);
        }
        return $optionText;
    }
}
