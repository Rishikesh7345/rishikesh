<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Block\Cart;

class AbstractCart
{
    /**
     * Get Category Id
     *
     * @param object $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemRenderer(\Magento\Checkout\Block\Cart\AbstractCart $subject, $result)
    {
        $result->setTemplate('Eighteentech_Buildbox::cart/item/default.phtml');
        return $result;
    }
}
