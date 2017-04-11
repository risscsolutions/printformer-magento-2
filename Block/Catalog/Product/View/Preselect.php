<?php
namespace Rissc\Printformer\Block\Catalog\Product\View;

use \Magento\Catalog\Block\Product\Context;
use \Magento\Framework\DataObject;
use \Magento\Framework\Stdlib\ArrayUtils;
use \Magento\Framework\UrlInterface;
use \Magento\Quote\Model\Quote;
use \Magento\Quote\Model\Quote\ItemFactory;
use \Magento\Catalog\Model\Product\OptionFactory;
use \Magento\Catalog\Model\Product\Option;
use \Rissc\PriceModels\Model\Pricemodel;
use \Magento\Customer\Model\Session as CustomerSession;

class Preselect
    extends \Magento\Catalog\Block\Product\View\AbstractView
{
    /** @var ItemFactory */
    protected $_itemFactory;

    /** @var OptionFactory */
    protected $_optionFactory;

    /** @var CustomerSession */
    protected $_session;

    /** @var UrlInterface */
    protected $_urlInterface;

    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        UrlInterface $urlInterface,
        ItemFactory $_itemFactory,
        OptionFactory $_optionFactory,
        CustomerSession $_session,
        array $data = []
    )
    {
        $this->_itemFactory = $_itemFactory;
        $this->_optionFactory = $_optionFactory;
        $this->_session = $_session;
        $this->_urlInterface = $urlInterface;

        parent::__construct($context, $arrayUtils, $data);
    }

    /**
     * @return string
     */
    public function getOptionsSaveUrl()
    {
        return $this->_urlInterface->getUrl('printformer/save/options');
    }

    /**
     * @return string
     */
    public function getOptionsGetUrl()
    {
        return $this->_urlInterface->getUrl('printformer/get/options');
    }

    /**
     * @return string
     */
    public function getDraftsSaveUrl()
    {
        return $this->_urlInterface->getUrl('printformer/save/draft');
    }

    /**
     * @return string
     */
    public function getDraftsGetUrl()
    {
        return $this->_urlInterface->getUrl('printformer/get/draft');
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

    public function getPreselection()
    {
        $_request = $this->getRequest();
        if($_request->getParam('id'))
        {
            /** @var Quote\Item $_quoteItem */
            $_quoteItem = $this->_itemFactory->create();
            $_quoteItem->getResource()->load($_quoteItem, (int)$_request->getParam('id'));
            $connection = $_quoteItem->getResource()->getConnection();

            if($_quoteItem->getId())
            {
                $result = $connection->fetchRow('
                        SELECT *
                          FROM ' . $connection->getTableName('quote_item_option') . '
                          WHERE
                            `item_id` = ' . $_quoteItem->getId() . ' AND
                            `code` = \'info_buyRequest\'
                    ');
                $buyRequest = new DataObject(unserialize($result['value']));

                $_fees = $buyRequest->getProductFees();
                $_product = $buyRequest->getProduct();
                $_options = $buyRequest->getOptions();
                $_qty = (int)$_quoteItem->getQty();

                $_returnJson = [
                    'product_options' => []
                ];

                $_priceModel = null;
                if(is_array($_options))
                {
                    foreach ($_options as $key => $_option)
                    {
                        $result = $connection->fetchRow('
                        SELECT *
                          FROM ' . $connection->getTableName('catalog_product_option_title') . '
                          WHERE
                            `option_id` = ' . $key . '
                        ');
                        if ($result['title'] != Pricemodel::DEFAULT_TITLE)
                        {
                            $_returnJson['product_options'][$key]['value'] = $_option;
                        } else if ($result['title'] == Pricemodel::DEFAULT_TITLE)
                        {
                            $_priceModel['value'] = $_option;
                        }
                    }
                }

                if($_priceModel != null)
                {
                    $_returnJson['product_options']['pricemodel'] = $_priceModel;
                }

                $_returnJson['product_options']['product'] = $_product;
                $_returnJson['product_options']['qty']['value'] = $_qty;

                if(is_array($_fees))
                {
                    foreach ($_fees as $key => $_fee)
                    {
                        $_returnJson['product_options']['product_fees'][$key]['value'] = $_fee;
                    }
                }

                return $_returnJson;
            }
        }
        return [];
    }
}