<?php
namespace Rissc\Printformer\Block\Catalog\Product\View;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Rissc\Printformer\Model\Product;

/**
 * Class GlobalVars
 * @package Rissc\Printformer\Block\Catalog\Product\View
 */
class GlobalVars
    extends Printformer
{
    public function getPreselection()
    {
        /** @var Product $product */
        $product = $this->getProduct();
        if (!$product) {
            return '{}';
        }
        $printformerData = json_decode($this->getJsonConfig(), true);
        $catalogSession = $this->sessionHelper->getCatalogSession();
        $preselectData = $catalogSession->getSavedPrintformerOptions();
        if(!empty($preselectData) || $this->isOnConfigurePDS())
        {
            if ($this->isOnConfigurePDS())
            {
                $printformerData['preselection'] = $this->getConfigurePreselection();
            }
            else
            {
                $printformerData['preselection'] = $preselectData;
            }
        }
        $printformerData['openControllerPreselect'] = true;

        return json_encode($printformerData);
    }

    /**
     * @return bool
     */
    public function isOnConfigurePDS()
    {
        $_request = $this->getRequest();
        $isConfigure = false;
        if(
            $_request->getModuleName() == 'checkout' &&
            $_request->getActionName() == 'configure'
        )
        {
            $isConfigure = true;
        }

        return $isConfigure;
    }

    public function getConfigurePreselection()
    {
        $_request = $this->getRequest();
        if($_request->getParam('id'))
        {
            /** @var Item $quoteItem */
            $quoteItem = ObjectManager::getInstance()->create('Magento\Quote\Model\Quote\Item');
            $quoteItem->getResource()->load($quoteItem, (int)$_request->getParam('id'));
            $connection = $quoteItem->getResource()->getConnection();

            if($quoteItem->getId())
            {
                $buyRequest = $quoteItem->getBuyRequest();

                $product = $buyRequest->getProduct();
                $options = $buyRequest->getOptions();
                $qty = (int)$quoteItem->getQty();

                $_returnJson = [];

                if (is_array($options))
                {
                    foreach ($options as $key => $_option)
                    {
                        $_returnJson['options'][$key]['value'] = $_option;
                    }
                }

                $_returnJson['product'] = $product;
                $_returnJson['qty']['value'] = $qty;

                $jsonDataObject = new DataObject($_returnJson);
                $this->_eventManager->dispatch(
                    'printformer_get_preselection_after',
                    ['connection' => $connection, 'options' => $options, 'json_data_object' => $jsonDataObject]
                );
                $_returnJson = $jsonDataObject->getData();

                return $_returnJson;
            }
        }
        return [];
    }
}