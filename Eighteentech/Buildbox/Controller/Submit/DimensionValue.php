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

class DimensionValue extends Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     */
    protected $jsonResultFactory;

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
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepositoryFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Session
     */
    protected $configurable;
    /**
     * @var Config
     */
    protected $config;

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
     * @param Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param Config $config
     * @param Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory
     * @param StoreManagerInterface $storeManager
     * @param Configurable $configurable
     * @param PriceCurrencyInterface $priceCurrency
     */

    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        ProductFactory $_productloader,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Eav\Model\Config $config,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
            $this->resultPageFactory    = $resultPageFactory;
            $this->resultRawFactory     = $resultRawFactory;
            $this->_productloader       = $_productloader;
            $this->jsonResultFactory    = $jsonResultFactory;
            $this->config               = $config;
            $this->productCollectionFactory = $productCollectionFactory;
            $this->_productRepositoryFactory = $productRepositoryFactory;
            $this->storeManager = $storeManager;
            $this->configurable = $configurable;
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
       
        // Add product dimention of each product
        $sum = 0;
        if (!empty($post['proDim'])) {
            foreach ($post['proDim'] as $key => $value) {
                $sum+= $value;
            }
        }
        $sizeArr=[];
        $arraynm = [];
        $productDim = [];
       //get product attribute size
        $attribute = $this->config->getAttribute('catalog_product', 'boxweight');
        $alloptions = $attribute->getSource()->getAllOptions();
        foreach ($alloptions as $option) {
            $label = $option['label'];
            $sizeArr[]=$label;
        }

        $product = $this->_productloader->create()->load($post['cartProduId']);
        $productionDay = $product->getProductionDay();

        //get product attribute size and check is product litter
        
        $sizeVal = count($sizeArr);
        for ($i=1; $i < $sizeVal; $i++) {
            if ($sizeArr[$i] >= $sum) {
                $productDim = $sizeArr[$i];
                break;
            }
        }
        $html = '';
        $optId = '';
        $parentId = [];
        $finalPrice = '';
        $_collection = $this->productCollectionFactory->create();
        $_collection->addAttributeToSelect('*');
        $_collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::
        STATUS_ENABLED);
        $isAttrExist = $_collection->getResource()->getAttribute('boxweight');
        
        if ($isAttrExist && $isAttrExist->usesSource()) {
            $optId = $isAttrExist->getSource()->getOptionId($productDim);
        }
        $_collection->addAttributeToFilter('boxweight', [$optId]);

        foreach ($_collection as $product) {
            $productid = $product->getId();
            $prod = $this->configurable->getParentIdsByChild($productid);
            if (isset($prod[0])) {
                $parentId[]= $prod[0];
            }
        }

        foreach (array_unique($parentId) as $parentProId) {
            $parentProd= $this->_productRepositoryFactory->create()->getById($parentProId);
            foreach ($_collection as $product) {
                $prod= $this->_productRepositoryFactory->create()->getById($parentProId);
                $store = $this->storeManager->getStore();
                $productImageUrl= $store->getBaseUrl(\Magento\Framework\UrlInterface::
                URL_TYPE_MEDIA) . 'catalog/product' . $product->getData('image');
           
                $prod = $this->configurable->getParentIdsByChild($product->getId());
                if (isset($prod[0])) {
                    $parentId[]= $prod[0];
                }
                if ($prod[0] == $parentProId) {
                    if ($post['prodQty'] >= $product->getMoqty()) {
                        $finalPrice = $this->priceCurrency->convertAndFormat($product->getFinalPrice(), 2);
                        $html .='<div class="box-1 product-box item box-card">
                            <div class="inner-box-1">
                                <div class="box-prod-image">        
                                    <img src="'.$productImageUrl .'"/>
                                </div>
                                <div class="box-prod-name">
                                    <h3>'. $parentProd->getName() .'</h3></div>
                                <div class="box-prod-price">
                                    <h3>Box Price:
                                    '. $finalPrice.'</h3>
                                </div>
                            </div> 
                            <input type="radio" name="choose-buildbox" 
                            value="'.$product->getId().'" 
                            class="choose-buildbox">
                            
                            <input type="hidden" name="box_parent_Id" 
                            value="'.$parentProId.'">
                            <input type="hidden" name="attributeId" value="'.$optId.'">
                        </div>';
                    }
                    break;
                }
            }
        }
        $result->setContents($html);
        return $result;
    }
}
