<?php

namespace Rissc\Printformer\Block\Adminhtml\Drafts\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;

class ProductName extends AbstractRenderer
{

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @param SerializerInterface $serializer
     */
    protected $serializer;

    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param SerializerInterface $serializer
     * @param Configurable $configurable
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        SerializerInterface $serializer,
        Configurable $configurable,
        array $data = []
    ) {
        $this->_productRepository = $productRepository;
        $this->serializer = $serializer;
        $this->configurable = $configurable;
        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        if ($row->getProductId()) {
            $product = $this->_productRepository->getById($row->getProductId());
            if ($product->getName()) {
                $html = "<h3>" . $product->getName() . "</h3>";
                if ($row->getSuperAttribute() && $product->getTypeId() == Configurable::TYPE_CODE) {
                    $superAttribute = $this->serializer->unserialize($row->getSuperAttribute());
                    $productAttributeOptions = $this->configurable->getConfigurableAttributesAsArray($product);
                    foreach ($productAttributeOptions as $key => $value) {
                        $tmp_option = $value['values'];
                        $optionSelected = $this->checkOption($superAttribute, $key);

                        if (count($tmp_option) > 0) {
                            $html .= $value['label'] . ": ";
                            foreach ($tmp_option as $tmp) {
                                if ($optionSelected == $tmp['value_index']) {
                                    $html .= $tmp['label'] . "</br>";
                                }
                            }
                        }
                    }
                }
                return $html;
            }
        }
        return __('Not assigned');
    }

    /**
     * @param $attributeId
     * @param $key
     * @return mixed
     */
    public function checkOption($attributeId, $key)
    {
        return $attributeId[$key]['value'];
    }
}
