<?php

namespace Aune\Stripe\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Aune\Stripe\Gateway\Helper\TokenProvider;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;
    
    /**
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
    }
    
    /**
     * @inheritdoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if(version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addStripeCustomerIdAttribute($setup);
        }
        
        $setup->endSetup();
    }
    
    /**
     * Add Stripe ID attribute to customer
     */
    private function addStripeCustomerIdAttribute(ModuleDataSetupInterface $setup)
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        
        $customerSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            TokenProvider::ATTRIBUTE_CODE,
            [
                'type' => 'varchar',
                'label' => 'Stripe Customer ID',
                'input' => 'text',
                'source' => '',
                'required' => false,
                'default' => '',
                'system' => false,
                'position' => 120,
                'sort_order' => 120,
                'adminhtml_only' => 1,
            ]
        );
    }
}
