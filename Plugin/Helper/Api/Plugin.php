<?php
namespace Rissc\Printformer\Plugin\Helper\Api;

use Magento\Framework\Phrase;
use Rissc\Printformer\Helper\Api;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;


class Plugin
{
    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var RequestInterface */
    protected $_request;

    /** @var ProductFactory */
    protected $_productFactory;

    /** @var ProductResource */
    protected $_productResource;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        ProductFactory $productFactory,
        ProductResource $productResource
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_request = $request;
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;
    }

    /**
     * @param Api      $subject
     * @param \Closure $oCreateDraftHash
     * @param          $masterId
     * @param          $userIdentifier
     * @param array    $params
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundCreateDraftHash(
        Api $subject,
        \Closure $oCreateDraftHash,
        $masterId,
        $userIdentifier,
        $params = []
    ) {
        $simpleProductId = $this->_request->getParam('product_id');
        $simpleProduct = $this->_productFactory->create();
        $this->_productResource->load($simpleProduct, $simpleProductId);

        $productAttribute = $this->_productResource->getAttribute('feed_identifier');
        $optionAttribute = $productAttribute->getIsVisible();

        if($optionAttribute == 1){
            $feedIdentifier = $productAttribute->getFrontend()->getValue($simpleProduct);
            return $oCreateDraftHash($masterId, $userIdentifier, array_merge($params, ['feedIdentifier' => $feedIdentifier]));
        }

        return $oCreateDraftHash($masterId, $userIdentifier, $params);
    }
}