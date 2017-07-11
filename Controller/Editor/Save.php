<?php
namespace Rissc\Printformer\Controller\Editor;

use Magento\Framework\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Session;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper as Helper;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Setup\InstallSchema;
use \Magento\Catalog\Model\Session as CatalogSession;

class Save
    extends Action
{
    const PERSONALISATIONS_QUERY_PARAM = 'personalizations';

    /** @var LoggerInterface */
    protected $logger;

    /** @var Cart */
    protected $cart;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var Helper\Session */
    protected $sessionHelper;

    /** @var Helper\Url */
    protected $urlHelper;

    /** @var Helper\Config */
    protected $configHelper;

    /** @var DraftFactory */
    protected $draftFactory;

    /** @var CatalogSession */
    protected $_catalogSession;

    /**
     * @param LoggerInterface $logger
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param Helper\Session $sessionHelper
     * @param Helper\Url $urlHelper
     * @param Helper\Config $configHelper
     * @param DraftFactory $draftFactory
     * @param CatalogSession $_catalogSession
     */
    public function __construct(
        LoggerInterface $logger,
        Context $context,
        ProductRepositoryInterface $productRepository,
        Helper\Session $sessionHelper,
        Helper\Url $urlHelper,
        Helper\Config $configHelper,
        DraftFactory $draftFactory,
        CatalogSession $_catalogSession
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->sessionHelper = $sessionHelper;
        $this->urlHelper = $urlHelper;
        $this->configHelper = $configHelper;
        $this->draftFactory = $draftFactory;
        $this->_catalogSession = $_catalogSession;
    }

    /* (non-PHPdoc)
     * @see \Magento\Framework\App\ActionInterface::execute()
     */
    public function execute()
    {
        $masterId  = $this->getRequest()->getParam('master_id');
        $this->_catalogSession->setPrintformerMasterid($masterId);

        $result = null;

        try {
            $productId       = $this->getRequest()->getParam('product_id');
            $draftId         = $this->getRequest()->getParam('draft_process');
            $storeId         = $this->getRequest()->getParam('store_id');

            $product = $this->productRepository->getById($productId, false, $storeId);
            $extraParams = [];

            $sessionUniqueId = $this->sessionHelper->getCustomerSession()->getSessionUniqueID();
            $uniqueID = null;
            if($sessionUniqueId)
            {
                $uniqueExplode = explode(':', $sessionUniqueId);
                if(isset($uniqueExplode[1]) && $product->getId() == $uniqueExplode[1])
                {
                    $uniqueID = $this->sessionHelper->getCustomerSession()->getSessionUniqueID();
                }
                else
                {
                    $uniqueID = md5(time() . '_' . $this->sessionHelper->getCustomerSession()->getCustomerId() . '_' . $product->getId()) . ':' . $product->getId();
                    $this->sessionHelper->getCustomerSession()->setSessionUniqueID($uniqueID);
                }
            }
            else
            {
                $uniqueID = md5(time() . '_' . $this->sessionHelper->getCustomerSession()->getCustomerId() . '_' . $product->getId()) . ':' . $product->getId();
                $this->sessionHelper->getCustomerSession()->setSessionUniqueID($uniqueID);
            }

            $draft = $this->draftFactory->create();
            $draft->getResource()->load($draft, $draftId);

            $draft->setSessionUniqueId($uniqueID);
            $draft->getResource()->save($draft);

            $url = $this->urlHelper
                ->setStoreId($storeId)
                ->getDraftUrl($draft->getDraftId());

            if($personalisations = $this->getPersonalisations($url))
            {
                $extraParams[self::PERSONALISATIONS_QUERY_PARAM][$storeId][$product->getId()] = $personalisations;
            }

            $params = $this->initDraft($product, $draftId, $storeId, $extraParams);

            if ($this->getRequest()->getParam('updateWishlistItemOptions') == 'wishlist/index/updateItemOptions') {
                // update wishlist item options if true
                $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD)
                    ->setParams($this->prepareUpdateWishlistItemOptionsParams($params))
                    ->setModule('wishlist')
                    ->setController('index')
                    ->forward('updateItemOptions');
            } elseif ($this->getRequest()->getParam('risscw2pnotepad')) { // add to wishlist if true
                $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD)
                    ->setParams($this->prepareAddToWishlistParams($params))
                    ->setModule('wishlist')
                    ->setController('index')
                    ->forward('add');
            } elseif ($this->configHelper->getConfigRedirect() // add to cart if true
                != \Rissc\Printformer\Model\Config\Source\Redirect::CONFIG_REDIRECT_URL_PRODUCT
            ) {
                $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD)
                    ->setParams($this->prepareAddToCartParams($params))
                    ->setModule('checkout')
                    ->setController('cart')
                    ->forward('add');
            } else { // redirect to product page
                $requestParams = [];
                if($this->getRequest()->getParam('project_id'))
                {
                    $requestParams[] = 'project_id=' . $this->getRequest()->getParam('project_id');
                }
                $result = $this->resultFactory
                    ->create(ResultFactory::TYPE_REDIRECT)
                    ->setUrl($this->urlHelper->getRedirectUrl($product) . (!empty($requestParams) ? '?' . implode('&', $requestParams) : ''));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            //@todo show some message to customer?
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param                                            $draftProcessId
     * @param                                            $storeId
     * @param array                                      $extra
     *
     * @return array
     */
    protected function initDraft(ProductInterface $product, $draftProcessId, $storeId, $extra = [])
    {
        $draftProcess = $this->draftFactory->create();
        $draftProcess->getResource()->load($draftProcess, $draftProcessId);

        if ($this->getRequest()->getParam('updateWishlistItemOptions') != 'wishlist/index/updateItemOptions') {
            $this->sessionHelper->setDraftId($product->getId(), $draftProcess->getDraftId(), $storeId);
        }

        /** @var Session $session */
        $session = $this->sessionHelper->getCatalogSession();
        foreach($extra as $key => $value) {
            $session->setData($key, $value);
        }

        $params = array(
            InstallSchema::COLUMN_NAME_DRAFTID => $draftProcess->getDraftId()
        );

        if ($this->getRequest()->getParam('super_attribute')) {
            $params['super_attribute'] = $this->getRequest()->getParam('super_attribute');
        }
        if ($this->getRequest()->getParam('options')) {
            $params['options'] = $this->getRequest()->getParam('options');
        }

        $formatVariation = $this->_getFormatVariation();
        $colorVariation  = $this->_getColorVariation();

        if ($formatVariation) {
            $this->_addSelectedProducFormat($params, $product, $formatVariation);
        }
        if ($colorVariation) {
            $this->_addSelectedProducColor($params, $product, $colorVariation);
        }

        return $params;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function prepareAddToCartParams(array $params)
    {
        $redirectUrl = $this->urlHelper->getRedirectUrl();
        if ($redirectUrl) {
            $params['redirect_url'] = $redirectUrl;
        }

        return $params;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function prepareAddToWishlistParams(array $params)
    {
        $redirectParams = array(
            'controller' => 'wishlist',
            'params' => array('_use_rewrite' => true)
        );

        $redirectUrl = $this->urlHelper->getRedirectUrl(null, $redirectParams);
        if ($redirectUrl) {
            $params['redirect_url'] = $redirectUrl;
        }

        return $params;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function prepareUpdateWishlistItemOptionsParams(array $params)
    {
        $redirectParams = array(
            'controller' => 'wishlist/index/configure',
            'params' => array(
                '_use_rewrite' => true,
                'product_id' => $this->getRequest()->getParam('product')
            )
        );

        $redirectUrl = $this->urlHelper->getRedirectUrl(null, $redirectParams);
        if ($redirectUrl) {
            $params['redirect_url'] = $redirectUrl;
        }

        return $params;
    }

    /**
     * @return integer
     */
    protected function _getFormatVariation()
    {
        return $this->getRequest()->getParam($this->configHelper->getFormatQueryParameter(), null);
    }

    /**
     * @return integer
     */
    protected function _getColorVariation()
    {
        return $this->getRequest()->getParam($this->configHelper->getColorQueryParameter(), null);
    }

    /**
     * @param array $params
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $variation
     * @return array
     */
    protected function _addSelectedProducFormat(array &$params, ProductInterface $product, $variation)
    {
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            return $this->_addSelectedProductAttribute(
                $params,
                $product,
                $variation,
                $this->configHelper->getFormatAttributeName(),
                $this->configHelper->getFormatAttributeValues()
            );
        } else {
            return $this->_addSelectedProductOption(
                $params,
                $product,
                $variation,
                $this->configHelper->getFormatOptionName(),
                $this->configHelper->getFormatOptionValues()
            );
        }
    }

    /**
     * @param array $params
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $variation
     * @return array
     */
    protected function _addSelectedProducColor(array &$params, ProductInterface $product, $variation)
    {
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            return $this->_addSelectedProductAttribute(
                $params,
                $product,
                $variation,
                $this->configHelper->getColorAttributeName(),
                $this->configHelper->getColorAttributeValues()
            );
        } else {
            return $this->_addSelectedProductOption(
                $params,
                $product,
                $variation,
                $this->configHelper->getColorOptionName(),
                $this->configHelper->getColorOptionValues()
            );
        }
    }

    protected function getPersonalisations($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if($err)
        {
            return null;
        }
        else
        {
            if(!empty($result))
            {
                $json = json_decode($result, true);
            }
            if(!isset($json['success']))
            {
                return null;
            }
            if(!empty($json) && is_array($json))
            {
                return (isset($json['data']['personalizations']['amount']) ? $json['data']['personalizations']['amount'] : null);
            }
        }
        return null;
    }

    /**
     * @param array $params
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $variation
     * @param string $attributeName
     * @param array $configAttributeValues
     * @return array
     */
    protected function _addSelectedProductAttribute(
        array &$params,
        ProductInterface $product,
        $variation,
        $attributeName,
        $configAttributeValues
    ) {
        $attributeId          = null;
        $attributeValueId     = null;
        foreach ($product->getAttributes() as $attribute) {
            if ($attribute->getAttributeCode() == $attributeName) {
                $attributeId = $attribute->getId();
            }
        }
        foreach ($configAttributeValues as $configAttributeValue) {
            if ($configAttributeValue['value'] == $variation) {
                $attributeValueId = $configAttributeValue['attr_id'];
            }
        }
        if ($attributeId && $attributeValueId) {
            $params['super_attribute'][$attributeId] = $attributeValueId;
        }
        return $params;
    }

    /**
     * @param array $params
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $variation
     * @param string $optionName
     * @param array $configOptionValues
     * @return array
     */
    protected function _addSelectedProductOption(
        array &$params,
        ProductInterface $product,
        $variation,
        $optionName,
        $configOptionValues
    ) {
        $optionId         = null;
        $optionValueId    = null;
        $optionValueTitle = null;
        foreach ($configOptionValues as $configOptionValue) {
            if ($configOptionValue['value'] == $variation) {
                $optionValueTitle = $configOptionValue['option'];
            }
        }
        foreach ($product->getOptions() as $option) {
            if ($option->getDefaultTitle() == $optionName) {
                $optionId = $option->getId();
                foreach ($option->getValues() as $optionValue) {
                    if ($optionValue->getTitle() == $optionValueTitle) {
                        $optionValueId = $optionValue->getId();
                    }
                }
            }
        }
        if ($optionId && $optionValueId) {
            $params['options'][$optionId] = $optionValueId;
        }
        return $params;
    }
}
