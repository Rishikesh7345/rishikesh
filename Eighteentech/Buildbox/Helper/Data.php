<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\ScopeInterface;

/**
 * Fetch data form admin
 */
class Data extends AbstractHelper
{
    /**
     * @var BUSINESS_CONFIG_PATH
     */
    private const BUSINESS_CONFIG_PATH = 'business_information';

    /**
     * @var XML_BOX_PATH
     */
    private const XML_BOX_PATH = 'buildbox/submit/config';

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param Cart $cart
     * @param Product $productFactory
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        Cart $cart,
        Product $productFactory
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->productFactory = $productFactory;
        $this->cart = $cart;
        parent::__construct($context);
    }

    /**
     * Get Config
     *
     * @param string $field
     * @param string $storeId
     * @return array
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    // public function getGeneralConfig($code, $storeId = null)
    // {

    //     return $this->getConfigValue(self::XML_BOX_PATH .'general/'. $code, $storeId);
    // }

    /**
     * Get Config
     *
     * @param string $config_path
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Add configurable Product
     *
     * @param int $parentId
     * @param int $childId
     */
    public function getAddConfigurableProduct($parentId, $childId)
    {
        $parent = $this->productFactory->load($parentId);
        $child = $this->productFactory->load($childId);

        $cart = $this->cart;

        $params = [];
        $params['product'] = $parent->getId();
        $params['qty'] = '1';
        $options = [];

        $productAttributeOptions = $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);

        foreach ($productAttributeOptions as $option) {
            $options[$option['attribute_id']] = $child->getData($option['attribute_code']);
        }
        $params['super_attribute'] = $options;

        /*Add product to cart */
        $cart->addProduct($parent, $params);
        $cart->save();
    }

    /**
     * Is Disabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return !$this->isEnabled();
    }

    /**
     * Registered number
     *
     * @param string $storeId
     * @return array
     */
    public function getRegisteredNumber($storeId = null)
    {
        return $this->getModuleConfig(self::BUSINESS_CONFIG_PATH . '/registered', $storeId);
    }

    /**
     * Get Warning Message
     *
     * @param string $storeId
     * @return array
     */
    public function getWarningMessage($storeId = null)
    {
        return $this->getModuleConfig(self::BUSINESS_CONFIG_PATH . '/message', $storeId);
    }

    /**
     * @param float $amount
     * @param bool $format
     * @param bool $includeContainer
     * @param null $scope
     *
     * @return float|string
     */

    /**
     * Convert Price
     *
     * @param float $amount
     * @param bool $format
     * @param bool $includeContainer
     * @param bool $scope
     * @return string
     */
    public function convertPrice($amount, $format = true, $includeContainer = true, $scope = null)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat(
                $amount,
                $includeContainer,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $scope
            )
            : $this->priceCurrency->convert($amount, $scope);
    }
}
