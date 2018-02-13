<?php
/**
 * Created by PhpStorm.
 * User: fabian
 * Date: 25.07.17
 * Time: 12:21
 */

namespace Rissc\Printformer\Block\Adminhtml\System\Catalog\Product;

use Rissc\Printformer\Model\ProductFactory as PrintformerProductFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;


class Capabilities  extends \Magento\Framework\View\Element\Template {

    /** @var PrintformerProductFactory  */
    protected $_printformerProductFactory;

    /** @var StoreManagerInterface  */
    protected $_storeManager;

    /** @var AttributeRepositoryInterface  */
    protected $_eavConfig;

    /**
     * Capabilities constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param PrintformerProductFactory $printformerProductFactory
     * @param AttributeRepositoryInterface $eavConfig
     * @param array $data
     */
    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                PrintformerProductFactory $printformerProductFactory,
                                AttributeRepositoryInterface $eavConfig,
                                array $data = []) {
        $this->_printformerProductFactory = $printformerProductFactory;
        $this->_storeManager = $context->getStoreManager();
        $this->_eavConfig = $eavConfig;

        parent::__construct($context, $data);
    }

    public function isDefiningNewProduct()
    {
        return $this->_request->getFullActionName() == 'catalog_product_new';
    }

    /**
     * @return string
     */
    public function getIntentsArray() {
        if (!$this->isDefiningNewProduct()) {
            //get current store id
            $storeID = $this->_storeManager->getStore()->getId();
            //get all printformer products for the current store from the database
            $printformerProductCollection = $this->_printformerProductFactory->create()->getCollection()->addFieldToFilter('store_id', ['eq' => $storeID])->load();

            //save all product master ids and the intents in an array
            $intentsArray = array();
            foreach($printformerProductCollection as $product) {
                $newIntentsArray = array();
                $intentsArray[$product->getMasterId()] = explode(",", $product->getIntents());
                foreach ($intentsArray[$product->getMasterId()] as $intent) {
                    array_push($newIntentsArray, $this->replaceIntentName($intent));
                }
                $intentsArray[$product->getMasterId()] = $newIntentsArray;
            }
            //return the json encoded array
            return json_encode($intentsArray);
        }
    }

    /**
     * @return string
     */
    public function getIntentsValue() {
        if (!$this->isDefiningNewProduct()) {
            $attribute = $this->_eavConfig->get(\Magento\Catalog\Model\Product::ENTITY, 'printformer_capabilities');
            $options = $attribute->getOptions();
            $intentsValueArray = array();
            foreach ($options as $option) {
                if (!empty($option->getLabel()) && !empty($option->getValue())) {
                    $intentsValueArray[$option->getLabel()] = $option->getValue();
                }
            }
            return json_encode($intentsValueArray);
        }
    }


    /**
     * @param $intent
     * @return string
     */
    private function replaceIntentName($intent) {
        switch ($intent) {
            case "customize":
                return "Editor";
                break;
            case "personalize":
                return "Personalizations";
                break;
            case "upload-and-editor":
                return "Upload and Editor";
                break;
            case "upload":
                return "Upload";
                break;
        }
    }

}