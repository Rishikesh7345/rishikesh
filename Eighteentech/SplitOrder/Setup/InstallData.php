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

namespace Eighteentech\SplitOrder\Setup;

use Magento\Framework\Module\Setup\Migration;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class InstallData implements InstallDataInterface
{
    protected $groupFactory;
    protected $customerSetupFactory;
    private $attributeSetFactory;
 
    public function __construct(
        GroupFactory $groupFactory,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->groupFactory = $groupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }
 
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        
        
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        
        $customerSetup->addAttribute(Customer::ENTITY, 'customer_type', [
            'type' => 'int',
            'label' => 'You Are A Company',
            'input' => 'boolean',
            'required' => false,
            'visible' => true,
            'source' => '',
            'backend' => '',
            'user_defined' => false,
            'is_user_defined' => false,
            'sort_order' => 90,
            'is_used_in_grid' => false,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 90,
            'default' => 0,
            'system' => 0,
         ]);
         
         $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'customer_type')
            ->addData([
                 'attribute_set_id' => $attributeSetId,
                 'attribute_group_id' => $attributeGroupId,
                 'used_in_forms' => ['customer_account_create', 'customer_account_edit', 'checkout_register','adminhtml_customer'],
          ]);
        
        
        $attribute->save();
        
 
        /*$group = $this->groupFactory->create();
        $group
            ->setCode('HR Group')
            ->setTaxClassId(3)
            ->save();
 */
        $setup->endSetup();
    }
}
