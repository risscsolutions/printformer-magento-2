<?php
namespace Rissc\Printformer\Model\Config\Source;

class Attribute extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $collection;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler
    ) {
        $this->objectManager = $objectManager;
        $this->configurableAttributeHandler = $configurableAttributeHandler;
        $this->collection = $configurableAttributeHandler->getApplicableAttributes();
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options[] = array(
                'label' => __('-- Please Select --'),
                'value' => ''
            );
            foreach($this->getProductAttributes() as $attribute) {
                if ($attribute->getFrontendInput() != 'select' || !$attribute->getIsUserDefined()) {
                    continue;
                }
                $this->_options[] = array(
                    'label' => $attribute->getAttributeCode(),
                    'value' => $attribute->getAttributeCode()
                );
            }
        }
        return $this->_options;
    }

    /**
     * @return \Magento\Eav\Model\ResourceModel\Attribute\Collection
     */
    protected function getProductAttributes()
    {
        return $this->collection;
    }
}
