<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @category Eighteentech
 * @package  Eighteentech_SplitOrder
 *
 */
 
namespace Eighteentech\SplitOrder\Block;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;

class CustomerGroups extends Template
{
    
    public $_customerGroup;
    
    public function __construct(
        CustomerGroup $customerGroup
    ) {
        $this->_customerGroup = $customerGroup;
    }

    public function getCustomerGroup()
    {
        $groups = $this->_customerGroup->toOptionArray();
        return $groups;
    }
}
