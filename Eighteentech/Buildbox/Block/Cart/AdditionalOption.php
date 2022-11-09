<?php

namespace Eighteentech\Buildbox\Block\Cart;

use Magento\Framework\View\Element\Template;

/**
 * Buildbox Additional option of proudct.
 */
class AdditionalOption extends Template
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory $_productloader
     */
    protected $_productloader;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder
     */
    protected $_imageBuilder;

    /**
     * @var \Magento\Catalog\Model\Product\Option $customOptions
     */
    protected $_customOptions;

    /**
     * Additional options constructor.
     *
     * @param Context $context
     * @param \Magento\Catalog\Model\ProductFactory $_productloader
     * @param \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder
     * @param \Magento\Catalog\Model\Product\Option $customOptions
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder,
        \Magento\Catalog\Model\Product\Option $customOptions,
        array $data = []
    ) {
        $this->_productloader = $_productloader;
        $this->_imageBuilder = $_imageBuilder;
        $this->_customOptions = $customOptions;
        parent::__construct($context, $data);
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
     * Get product attribute group options
     *
     * @param string $type
     * @return object
     */
    public function getGroupOfOption($type)
    {
        $group = $this->_customOptions->getGroupByType($type);
        return $group == '' ? 'default' : $group;
    }
}
