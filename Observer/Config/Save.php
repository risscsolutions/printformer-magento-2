<?php

namespace Rissc\Printformer\Observer\Config;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;

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

    public function __construct(
        LoggerInterface $logger,
        ApiHelper $_apiHelper,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->logger = $logger;
        $this->_apiHelper = $_apiHelper;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws GuzzleException
     */
    public function execute(Observer $observer)
    {
        $resultName = '';
        try {
            $storeId = false;
            $websiteId = $observer->getWebsite();
            if (empty($websiteId)) {
                $storeId = 0;
            }
            $url = $this->_apiHelper->apiUrl()->getClientName($storeId, $websiteId);
            $httpClient = $this->_apiHelper->getHttpClient($storeId, $websiteId);
            $response = $httpClient->get($url);
            $response = json_decode($response->getBody(), true);
            $resultName = $response['data']['name'];
        } catch (\Exception $exception) {
            $message = __('Error setting name client configuration. Empty Response. Url: ' . $url);
            $this->logger->debug($message);
        }

        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;

        $website = (int)$observer->getEvent()->getWebsite();
        if (!empty($website)) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $website;
        }
        $this->configWriter->save(ConfigHelper::XML_PATH_V2_NAME, $resultName, $scope, $scopeId);
        $this->cacheTypeList->cleanType('config');
    }

}
