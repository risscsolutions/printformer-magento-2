<?php

namespace Rissc\Printformer\Observer\Admin\Product;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Helper\Config;

/**
 *
 */
class GetFeedName implements ObserverInterface
{

    /**
     * @var Api
     */
    private $apiHelper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Config
     */
    protected $configHelper;

    const DEFAULT_NAME = '';


    /**
     * @param Api $apiHelper
     * @param ManagerInterface $messageManager
     * @param Config $configHelper
     */
    public function __construct(
        Api              $apiHelper,
        ManagerInterface $messageManager,
        Config           $configHelper
    )
    {
        $this->apiHelper = $apiHelper;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;

    }

    public function execute(EventObserver $observer)
    {
        $product = $observer->getProduct();
        $feedIdentifier = $product->getFeedIdentifier();
        $storeId = $product->getStoreId();
        $enabled = $this->configHelper->isEnableFeedIdentifier($storeId);
        $name = self::DEFAULT_NAME;
        if ($feedIdentifier && $enabled) {
            try {
                $url = $this->apiHelper->apiUrl()->getProductFeedName($feedIdentifier);
                $httpClient = $this->apiHelper->getHttpClient($storeId);

                $response = $httpClient->get($url);
                $response = json_decode($response->getBody(), true);
                if ($response['data']['name']) {
                    $name = $response['data']['name'];
                    $product->setFeedName($name);
                }
                $product->addAttributeUpdate("feed_name", $name, $storeId);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('We can\'t update product Feed Name right now: %1.', $e->getMessage())
                );
            }
        }
    }
}
