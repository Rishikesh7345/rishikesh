<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\BoxEdit\Block\Cart;

use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Eighteentech\Buildbox\Helper\Data;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;

/**
 * Create Buildbox Button.
 *
 * Buildbox Button In cart.
 */
class BoxEdit extends Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\CXML_BOX_PATHategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productloader;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepo;

    /**
     * @var ObjectManager
     */
    private $_objectManager;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var _productCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ImageBuilder
     */
    protected $_imageBuilder;

    /**
     * @var Option
     */
    protected $_customOptions;
    /**
     * Button constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param ProductRepository $productRepository
     * @param Configurable $configurable
     * @param PriceCurrencyInterface $priceCurrency
     * @param Data $helper
     * @param ProductFactory $_productloader
     * @param FormKey $formKey
     * @param Cart $cart
     * @param Product $product
     * @param CollectionFactory $productCollectionFactory
     * @param CategoryFactory $categoryFactory
     * @param ObjectManagerInterface $objectmanager
     * @param ImageBuilder $_imageBuilder
     * @param Option $customOptions
     * @param array $data
     */
  
    public function __construct(
        Context $context,
        Session $checkoutSession,
        ProductRepository $productRepository,
        Configurable $configurable,
        PriceCurrencyInterface $priceCurrency,
        Data $helper,
        ProductFactory $_productloader,
        FormKey $formKey,
        Cart $cart,
        Product $product,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder,
        \Magento\Catalog\Model\Product\Option $customOptions,
        array $data = []
    ) {
        $this->checkoutSession    = $checkoutSession;
        $this->_productRepository = $productRepository;
        $this->configurable       = $configurable;
        $this->priceCurrency      = $priceCurrency;
        $this->helper             = $helper;
        $this->_productloader     = $_productloader;
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->product = $product;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->_objectManager = $objectmanager;
        $this->_imageBuilder = $_imageBuilder;
        $this->_customOptions = $customOptions;
        parent::__construct($context, $data);
    }
    /**
     * Get product price for formate
     *
     * @param int $price
     * @return int
     */
    public function getCurrencyWithFormat($price)
    {
        return $this->priceCurrency->convertAndFormat($price, 2);
    }

    /**
     * Get parent product
     *
     * @param int $childProductId
     * @return bool
     */
    public function getParentProductId($childProductId)
    {
        $parentConfigObject = $this->configurable->getParentIdsByChild($childProductId);
        
        if ($parentConfigObject) {
           
            return $parentConfigObject[0];
        }
        return false;
    }
    
    /**
     * Get Load product
     *
     * @param int $id
     * @return array
     */
    public function getLoadProduct($id)
    {
        return $this->_productloader->create()->load($id);
    }

    /**
     * Get product image
     *
     * @param array $product
     * @param array $attributes
     * @return array
     */
    public function getImage($product, $attributes = [])
    {
        $imageId = 'product_base_image';
        return $this->_imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     * Get Product Image
     *
     * @return string
     */
    public function getBuildboxImage()
    {
        $storeManager = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        $store = $storeManager->getStore();
        $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl;
    }

    /**
     * Get product product data
     *
     * @param array $data
     * @return customOption
     */
    public function getCustomOptions($data)
    {
        return $this->_customOptions->getProductOptionCollection($data);
    }

    /**
     * Get product product data
     *
     * @param int $prd_data
     * @param string $option
     * @return object
     */
    public function getOptionHtml($prd_data, \Magento\Catalog\Model\Product\Option $option)
    {
        $type = $this->getGroupOfOption($option->getType());
        $renderer = $this->getChildBlock($type);
        $renderer->setProduct($prd_data)->setOption($option);
        return $this->getChildHtml($type, false);
    }

    /**
     * Get Options type
     *
     * @param string $type
     * @return object
     */
    public function getGroupOfOption($type)
    {
        $group = $this->_customOptions->getGroupByType($type);
        return $group == '' ? 'default' : $group;
    }
    
    /**
     * Get attribute option id
     */
    public function getAttrOptIdByLabel()
    {
        $product = $this->_productloader->create();
        
        $attributes = $product->getAttributes();
        foreach ($attributes as $a) {
            $a->getBoxweight()."<br>";
        }
    }
    
    /**
     * Get product size
     *
     * @param int $size
     * @return collection
     */
    public function getProductByAttribute($size)
    {
        $_collection = $this->productCollectionFactory->create();

        $_collection->addAttributeToSelect('*');
        $_collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::
        STATUS_ENABLED);
        // $size =
        // Your attribute code
        $isAttrExist = $_collection->getResource()->getAttribute('boxweight'); // Add here your attribute code
        $optId = '';
        if ($isAttrExist && $isAttrExist->usesSource()) {
            $optId = $isAttrExist->getSource()->getOptionId($size);
        }
        $_collection->addAttributeToFilter('boxweight', [$optId]);
        return $_collection;
    }

    /**
     * Get product Id
     *
     * @param int $productId
     * @return bool
     */
    public function getCheckProduct($productId)
    {
        $product = $this->_productloader->create()->load($productId);
        $isBox = '';
        if ($product->getProdinbox() == true) {
            $isBox = 1;
        }
        return $isBox;
    }

    /**
     * Get product Id
     *
     * @param int $productId
     * @return int
     */
    public function getDimension($productId)
    {
        $product = $this->_productloader->create()->load($productId);
        $height = $product->getHeight();
        $width = $product->getWidth();
        $lenght = $product->getLenght();
        $totDem = ($height * $width * $lenght)/1000;
        return $totDem;
    }

    /**
     * Get allowed attributes
     */
    public function getAllowAttributes()
    {
        return $this->getProduct()->getTypeInstance()->getConfigurableAttributes($this->getProduct());
    }
    
    /**
     * Get Category Id
     *
     * @param string $ids
     * @return array
     */
    public function getProductCollectionByCategories($ids)
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addCategoriesFilter([$ids]);
        return $collection;
    }

    /**
     * Get Product Image
     *
     * @param string $id
     * @return array
     */
    public function getBoxImage($id)
    {
        $product = $this->_objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class)->getById($id);
        $store = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore();
        return [$product,$store];
    }

    /**
     * Get Cart Image
     *
     * @param string $cartProid
     * @return array
     */
    public function getCartImage($cartProid)
    {
        $product = $this->_objectManager->create(\Magento\Catalog\Model\Product::class)->load($cartProid);
        $cartProImg = $product->getMediaGalleryImages();
        return $cartProImg;
    }

    /**
     * Get Admin configuration field value
     */
    public function getConfigValue()
    {
        return $this->helper->getConfig('box_config/fields_masks/category_id');
    }

    /**
     * Product Collection
     */
    public function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setPageSize(3);
        $categoryId = $this->helper->getConfig('box_config/fields_masks/category_id');
        $category = $this->categoryFactory->create()->load($categoryId);
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addCategoryFilter($category);
       
        $collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::
        STATUS_ENABLED);
        return $collection;
    }

    /**
     * Get Quote Items
     *
     * @param string $quote
     * @return array
     */
    public function getItems($quote = null)
    {
        try {
            $quote = $quote ?: $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException $e) {
            return null;
        } catch (LocalizedException $e) {
            return null;
        }

        // $quote->getItemsCollection()->getName();
        
        return $quote->getItemsCollection();
    }

    /**
     * Check Configurable Product
     *
     * @param string $item
     * @return array
     */
    public function checkConfigurableProduct($item)
    {
        return $this->configurable->getParentIdsByChild($item->getProductId());
    }

    /**
     * Get Name Configurable
     *
     * @param string $item
     * @return array
     */
    public function getNameConfigurable($item)
    {
        try {
            if ($product = $this->_productRepository->get($item->getSku())) {
                return $product->getName();
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return null;
    }

    /**
     * Change Price
     *
     * @param string $price
     * @return array
     */
    public function formatPrice($price)
    {
        return $this->helper->convertPrice($price, true, false);
    }

    /**
     * Get Base grand total
     *
     * @param string $quote
     * @return array
     */
    public function getBaseGrandTotal($quote = null)
    {
        $quote = $this->getQuote($quote);

        return $quote ? $this->formatPrice($quote->getBaseGrandTotal()) : null;
    }

    /**
     * Get Quote Item
     *
     * @param string $quote
     * @return array
     */
    public function getQuote($quote = null)
    {
        try {
            $quote = $quote ?: $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException $e) {
            return null;
        } catch (LocalizedException $e) {
            return null;
        }

        return $quote;
    }

    /**
     * Items Count
     */
    public function getItemsCount()
    {
        $quote = $this->getQuote();
        return $quote ? $quote->getItemsCount() : null;
    }

    /**
     * Enable
     */
    public function isEnable()
    {
        return $this->helper->isEnabled();
    }
}
