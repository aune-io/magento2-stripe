<?php

namespace Aune\Stripe\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Aune\Stripe\Gateway\Helper\TokenProvider;

/**
 * @codeCoverageIgnore
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
 
    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
    
    /**
     * @inheritdoc
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create();
        
        $attributes = [
            TokenProvider::ATTRIBUTE_CODE,
        ];
        
        foreach($attributes as $attribute) {
            $eavSetup->removeAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                $attribute
            );
        }
 
        $setup->endSetup();
    }
}
