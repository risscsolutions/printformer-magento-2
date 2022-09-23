<?php

namespace Rissc\Printformer\Observer\Config;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Gateway\Exception;
use Rissc\Printformer\Helper\Api as ApiHelper;
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
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $url = $this->_apiHelper->apiUrl()->getClientName();
        $httpClient = $this->_apiHelper->getHttpClient();

        $response = $httpClient->get($url);
        $response = json_decode($response->getBody(), true);
        $name = $response['data']['name'];
        if (empty($name)) {
            $message = __('Error setting name client configuration. Empty Response. Url: ' . $url);
            $this->logger->debug($message);
            throw new Exception($message);
        }
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;

        $website = (int)$observer->getEvent()->getWebsite();
        if (!empty($website)) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $website;
        }
        $this->configWriter->save('printformer/version2group/v2clientName', $name, $scope, $scopeId);
        $this->cacheTypeList->cleanType('config');
    }

}
