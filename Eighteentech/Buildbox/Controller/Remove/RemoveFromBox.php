<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Controller\Remove;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\QuoteRepository;

/**
 * Index Controller
 */
class RemoveFromBox extends Action
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
        \Magento\Catalog\Model\ProductFactory $productFactory
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
        parent::__construct($context);
    }

    /**
     * Execute build box functionality
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $quoteId = $this->cart->getQuote()->getId();
        $itemsArray = $this->cart->getQuote()->getAllItems();

        $countId = [];
        $i = 0;
        $quote = $this->cart->getQuote();
        $itemById = $quote->getItemById($post['boxProId']);
        $boxProductId = $itemById->getBoxProductId();
        
        $boxId = $itemById->getBoxItemId();
        if ($itemById->getBoxType() != null) {
            $itemById->setBoxType(null);
            $itemById->setBoxProductId(null);
            $item->setEsdcPricing(null);
            $item->setBoxItemId(null);
            $quote1 = $this->quoteRepository->get($itemById->getQuoteId());
            $quote1->setData('esdc_enable', null);
            $this->quoteRepository->save($quote1);
            $itemById->save();
        }
        foreach ($itemsArray as $item) {
            if ($item->getItemId() != $boxId) {
                $items = $quote->getItemById($item->getId());
                if ($item->getId() == $boxId) {
                    $item->delete();
                    $item->save();
                    continue;
                }
            }
        }
        $this->cart->save();
       //message for response
        $data = ['success' => 'true',
        'msg' => 'Product added to cart successfully!'];
        $result = $this->jsonResultFactory->create();
        $result->setData($data);
        return $result;
    // }
    }
}
