<?php
namespace Rissc\Printformer\Gateway\User;

use Rissc\Printformer\Gateway\Exception;

class Draft
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactor
     */
    protected $httpClientFactory;

    /**
     * @var \Magento\Framework\Json\Decoder
     */
    protected $jsonDecoder;

    /**
     * @var \Rissc\Printformer\Helper\Url
     */
    protected $urlHelper;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Magento\Framework\Json\Decoder $jsonDecoder
     * @param \Rissc\Printformer\Helper\Url $urlHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Json\Decoder $jsonDecoder,
        \Rissc\Printformer\Helper\Url $urlHelper
    ) {
        $this->logger = $logger;
        $this->httpClientFactory = $httpClientFactory;
        $this->jsonDecoder = $jsonDecoder;
        $this->urlHelper = $urlHelper;
    }

    /**
     * @param integer $draftId
     * @param integer $storeId
     * @throws Exception
     * @return \Rissc\Printformer\Gateway\User\Draft
     */
    public function deleteDraft($draftId, $storeId)
    {
        $url = $this->urlHelper
            ->setStoreId($storeId)
            ->getDraftDeleteUrl($draftId);

        $this->logger->debug($url);

        /** @var \Zend_Http_Response $response */
        $response = $this->httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(\Zend_Http_Client::POST);

        if (!$response->isSuccessful()) {
            throw new Exception(__('Error deleting draft.'));
        }
        $responseArray = $this->jsonDecoder->decode($response->getBody());
        if (!is_array($responseArray)) {
            throw new Exception(__('Error decoding response.'));
        }
        if (isset($responseArray['success']) && false == $responseArray['success']) {
            $errorMsg = 'Request was not successful.';
            if (isset($responseArray['error'])) {
                $errorMsg = $responseArray['error'];
            }
            throw new Exception(__($errorMsg));
        }

        return $this;
    }

    /**
     * @param integer $draftId
     * @param integer $storeId
     * @throws Exception
     * @return \Rissc\Printformer\Gateway\User\Draft
     */
    public function getDraft($draftId)
    {
        $url = $this->urlHelper
            ->getDraftDeleteUrl($draftId);

        $this->logger->debug($url);

        /** @var \Zend_Http_Response $response */
        $response = $this->httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(\Zend_Http_Client::POST);

        if (!$response->isSuccessful()) {
            throw new Exception(__('Error deleting draft.'));
        }
        $responseArray = $this->jsonDecoder->decode($response->getBody());
        if (!is_array($responseArray)) {
            throw new Exception(__('Error decoding response.'));
        }
        if (isset($responseArray['success']) && false == $responseArray['success']) {
            $errorMsg = 'Request was not successful.';
            if (isset($responseArray['error'])) {
                $errorMsg = $responseArray['error'];
            }
            throw new Exception(__($errorMsg));
        }

        return $this;
    }
}
