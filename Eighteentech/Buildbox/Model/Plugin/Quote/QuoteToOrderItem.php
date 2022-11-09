<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */

namespace Eighteentech\Buildbox\Model\Plugin\Quote;

use Closure;

/**
 * Convert Quote into OrderItem
 */
class QuoteToOrderItem
{
    
    /**
     * Constructor.
     *
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param callable $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $additional
     * @return \Magento\Sales\Model\Order\Item
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        $orderItem = $proceed($item, $additional);
        $orderItem->setBoxId($item->getBoxId());
        $orderItem->setBoxType($item->getBoxType());//set your required
        $orderItem->setBoxProductId($item->getBoxProductId());
        $orderItem->setBoxName($item->getBoxName());
        $orderItem->setBoxItemId($item->getBoxItemId());
        return $orderItem;
    }
}
