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

/**
 * Box name edit Controller
 */
class BoxNameEdit extends Action
{
   
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var jsonResultFactory
     */
    protected $jsonResultFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param FormKey $formKey
     * @param Cart $cart
     * @param JsonFactory $jsonResultFactory
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->jsonResultFactory = $jsonResultFactory;
        parent::__construct($context);
    }

    /**
     * Execute build box functionality
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        /**
         * @var $itemid
         * store edit box product item it
         */
        $itemid = $post['edit-prodItemId'];

        /**
         * @var $itemid
         * store edit box Name
         */
        $boxname = $post['edit-box-name'];
        $quote = $this->cart->getQuote();
        $item = $quote->getItemById($itemid);
        $item->setBoxName($boxname);
        $item->save();
        //message for response
        $data = ['success' => 'true', 'msg' => 'Product added to cart successfully!'];
        $result = $this->jsonResultFactory->create();
        $result->setData($data);
        return $result;
    }
}
