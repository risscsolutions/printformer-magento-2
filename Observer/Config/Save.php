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
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Helper\Customer\Group\PrintformerUserGroup;

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
     * @var GroupCollection
     */
    private $groupCollection;

    /**
     * @var Api
     */
    private $printformerApi;

    /**
     * @var PrintformerUserGroup
     */
    private $printformerUserGroup;

    public function __construct(
        LoggerInterface $logger,
        ApiHelper $_apiHelper,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        GroupCollection $groupCollection,
        Api $printformerApi,
        PrintformerUserGroup $printformerUserGroup
    ) {
        $this->logger = $logger;
        $this->_apiHelper = $_apiHelper;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->groupCollection = $groupCollection;
        $this->printformerApi = $printformerApi;
        $this->printformerUserGroup = $printformerUserGroup;
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

        if (!empty($resultName)) {
            $this->handleUserGroups();
        }
    }

    private function handleUserGroups(): void
    {
        $userGroupIds = $this->groupCollection->addFieldToSelect('customer_group_id')->getAllIds();
        $userGroupsExistIds = $this->getPrintformerUserGroups($userGroupIds);

        $userGroupToCreate = array_values(array_diff($userGroupIds, $userGroupsExistIds));
        foreach ($userGroupToCreate as $magentoGroupId) {
            $this->printformerUserGroup->deleteUserGroup($magentoGroupId);
            $this->printformerApi->createUserGroup($magentoGroupId);
        }
    }

    private function getPrintformerUserGroups(array $groupIds): array
    {
        $userGroupsExist = [];
        foreach ($groupIds as $magentoGroupId) {
            $userGroupIdentifier = $this->printformerUserGroup->getUserGroupIdentifier($magentoGroupId);
            if (empty($userGroupIdentifier)) {
                continue;
            }

            $userGroup = $this->printformerApi->getUserGroup($userGroupIdentifier);
            if (!empty($userGroup)) {
                $userGroupsExist[] = $magentoGroupId;
            }
        }

        return $userGroupsExist;
    }
}
