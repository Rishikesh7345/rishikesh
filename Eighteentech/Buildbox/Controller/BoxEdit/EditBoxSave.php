<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Controller\BoxEdit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\QuoteRepository;

/**
 * Index Controller
 */
class EditBoxSave extends Action
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

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
     * @var _productCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var jsonResultFactory
     */
    protected $jsonResultFactory;

    /**
     * @var jsonResultFactory
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_catalogProductTypeConfigurable;
    
    /**
     * @var \Magento\Catalog\Model\Product\Option $customOptions
     */
    protected $customOptions;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param FormKey $formKey
     * @param Cart $cart
     * @param CollectionFactory $productCollectionFactory
     * @param Product $product
     * @param JsonFactory $jsonResultFactory
     * @param ResourceConnection $resource
     * @param Session $checkoutSession
     * @param Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param ProductFactory $productFactory
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     * @param \Magento\Catalog\Model\Product\Option $customOptions
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Cart $cart,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        Product $product,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Catalog\Model\Product\Option $customOptions,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->product = $product;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->resource = $resource;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->productFactory = $productFactory;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_customOptions = $customOptions;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute build box functionality
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();   

        $itemInBox = 0;
        if (!empty($post['itemInBox'])) {
            $itemInBox = 1;
        } else {
            $itemInBox = 0;
        }

        /**
         * @var $sum
         * Store sum of proudcts dimension
         */
        $sum = 0;

        /**
         * @var $proDim
         * Store proudcts dimension value
         */
        $proDim=[];
        foreach ($post['prodDim'] as $key => $value) {
            $proDim[] = $value;
            $sum += $value;
        }

        /**
         * @var $childWCId
         * Get child product id and options id
         */
        $childWCId = '' ;
        if (!isset($post["optionProId"])) {
            $childWCId = $post['choose-buildbox'];
        } else {
            $childWCId = $post["optionProId"];
        }
        $productId = '';
        $parentByChild = $this->_catalogProductTypeConfigurable->getParentIdsByChild($childWCId);
        if (isset($parentByChild[0])) {
            //set id as parent product id...
            $productId = $parentByChild[0];
        }

        /**
         * @var $childProduct
         * Load product by product id
         */
        $childProduct = $this->productFactory->create()->load($childWCId);
        $parent = $this->productFactory->create()->load($productId);

        /**
         * @var $params
         * Store product parameters
         */
        $params = [];
      
        $params = array(
            'form_key' => $this->formKey->getFormKey(),
            'product' => $parent->getId(), 
            'qty'   => $post['editBoxQty']
        );

        /**
         * @var $prodId
         * Store product id
         */
        $prodId=[];

        /**
         * @var $itemNum
         * get store item Number
         */
        $itemNum = '';

        /**
         * @var $getItemsArry
         * get All cart items
         */
        $productAttributeOptions = $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);
        $customOptions = $this->_customOptions->getProductOptionCollection($parent);
        $optionId = '';
        foreach ($customOptions as $option) {
            $optionId = $option->getId();
        }

        foreach ($productAttributeOptions as $option) {
            $options[$option['attribute_id']] = $childProduct->getData($option['attribute_code']);
        }
        $params['super_attribute'] = $options;
		
        $quote = $this->cart->getQuote();
        $getItemsArry = $this->cart->getQuote()->getAllItems();
        foreach ($getItemsArry as $items) {
            $quoteItemCollection = $this->quoteItemCollectionFactory->create();
            $quoteItemCollection->addFieldToSelect('*')
                ->addFieldToFilter('item_id', $items->getItemId())
                ->addFieldToFilter('box_product_id', $post['existId'])
                ->addFieldToFilter('box_item_id', $post['productItemId'])
                ->getFirstItem();
        
            foreach ($quoteItemCollection as $item) {	
            
                $remItem = $quote->getItemById($item->getItemId());
                $remItem->setBoxProductId(null);
                $remItem->setBoxType(null);
                $remItem->setBoxItemId(null);
                $remItem->setProductDim(null);
                $remItem->save();                
            }            
            if ($items->getItemId()==$post['editItemId']) {  
                $items->delete();
                $items->save();
                continue;
            }			
         
        }
        
        /**
         * Add custom column value in cart product with box
         */ 
        if (!empty($post['getItem'])) {
            $avbQty = '';
            $prodId = $post['getItem'];
            $itemNum = count($post['getItem']);
            
            for ($i = 0; $i < $itemNum; $i++) {
                $quote = $this->cart->getQuote();
                $item = $quote->getItemById($prodId[$i]);
                $item->setBoxType("yes"); //don't change
                $item->setBoxProductId($productId);
                $item->setQty($post['editBoxQty']);
                
                if ($parent->getId()==$item->getBoxProductId()) {
                    $item->setProductDim($proDim[$i]);
                }               
                $option = array(
                    $optionId  => $parent->getName().'_'. $item->getItemId()
                );

                $item->save();
            }
        }
        $params['options'] = $option;
        $this->cart->addProduct($parent, $params);
        $this->cart->save();

        /**
         * @var $data
         * json message for response
         */
        $data = ['success' => 'true', 'msg' => 'Cart product Edit successfully!'];
        $result = $this->jsonResultFactory->create();
        $result->setData($data);

        /**
         * @var $itemsArray
         * Get all Cart item
         */
        $itemsArray = $this->cart->getQuote()->getAllItems();

        /**
         * @var $itemId
         * Store Cart Itemid when productid == exist product id in cart
         */
        $itemId ='';
        $parentId = '';
        $ItemProId = '';
        $getBoxItems = [];
        foreach ($itemsArray as $items) {
            if ($items->getProductId() == $productId) {
                $itemId = $items->getItemId();
                $parentId = $items->getItemId();
                $quotebox = $this->cart->getQuote();
                $setBoxId = $quotebox->getItemById($itemId);
                $getBoxItems[] = $setBoxId->getItemId();
                $setBoxId->setBoxId(1);
               
                if($setBoxId->getBoxId()=='1'){
                    $ItemProId = $setBoxId->getItemId();
                }
                $setBoxId->save();
            }
            if(!empty($parentId) && ($parentId == $items->getParentItemId())){
                
                $additionalOptions = $items->getOptionByCode('info_buyRequest');
                $quotebox = $this->cart->getQuote();
                $parentItem = $quotebox->getItemById($parentId);
                $buyRequest =$additionalOptions->getValue();
                $parentItem->getOptionByCode('info_buyRequest')->setValue($buyRequest);   
                $parentItem->saveItemOptions();
                $parentItem->save();
            }
        }
        if (!empty($post['getItem'])) {
            $prodId = $post['getItem'];
            $itemNum = count($post['getItem']);
            for ($i = 0; $i < $itemNum; $i++) {
                $quote = $this->cart->getQuote();
                $item = $quote->getItemById($prodId[$i]);
                $item->setBoxItemId($ItemProId);
                $item->save();
            }
        }
        if (!empty($getBoxItems)) {
            $itemNumco = count($getBoxItems);
            for ($i = 0; $i < $itemNumco; $i++) {
                $quote = $this->cart->getQuote();
                $item = $quote->getItemById($getBoxItems[$i]);
                if(empty($item->getBoxName())){
                    $item->getItemId();
                    $quote = $this->cart->getQuote();
                    $items = $quote->getItemById($item->getItemId());
                    $items->setQty($post['editBoxQty']);
                    $items->setBoxName($post['existBoxName']);
                }
                $item->save();
            }
        } 
        return $result;
    }
}
