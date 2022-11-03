<?php

namespace Rissc\Printformer\Observer\Config;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Gateway\Exception;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Store\Api\StoreWebsiteRelationInterface;

class Save implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var ApiHelper
     */
    protected $_apiHelper;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var StoreWebsiteRelationInterface
     */
    protected $storeWebsiteRelation;

    public function __construct(
        LoggerInterface $logger,
        ApiHelper $_apiHelper,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        StoreWebsiteRelationInterface $storeWebsiteRelation
    ) {
        $this->logger = $logger;
        $this->_apiHelper = $_apiHelper;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
    }

    /**
     * @param Observer $observer
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $website = (int)$observer->getEvent()->getWebsite();
        if (!empty($website)) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $website;
            $storeIds = $this->getStoreIds($website);
            //Get only first store
            $storeId = $storeIds[0];
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
            $storeId = 0;
        }
        try {
            $url = $this->_apiHelper->apiUrl()->getDefaultClientName($storeId);
            $httpClient = $this->_apiHelper->getDefaultHttpClient($storeId);
            $response = $httpClient->get($url);
            $response = json_decode($response->getBody(), true);
            $name = $response['data']['name'];
            if (empty($name)) {
                $message = __('Error setting name client configuration. Empty Response. Url: ' . $url);
                $this->logger->error($message);
                throw new Exception($message);
            }
            $this->configWriter->save(ConfigHelper::XML_PATH_V2_NAME, $name, $scope, $scopeId);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->configWriter->save(ConfigHelper::XML_PATH_V2_NAME, null, $scope, $scopeId);
        }
        $this->cacheTypeList->cleanType('config');
    }

    /**
     * @param $websiteId
     * @return array
     * @throws Exception
     */
    public function getStoreIds($websiteId)
    {
        $storeId = [];
        try {
            $storeId = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);
        } catch (Exception $exception) {
            $this->logger->debug($exception->getMessage());
            throw new Exception($exception->getMessage());
        }

        return $storeId;
    }

}
