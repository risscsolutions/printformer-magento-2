<?php
namespace Rissc\Printformer\Helper\Api\Url;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Rissc\Printformer\Helper\Api\VersionInterface;
use Rissc\Printformer\Helper\Config;

/**
 * Class V1
 * @package Rissc\Printformer\Helper\Api\Url
 * @deprecated
 */
class V1 extends AbstractHelper implements VersionInterface
{
    const ROLE_USER  = '0';
    const ROLE_ADMIN = '1';

    const URI_ADMIN_PRODUCTS            = 'api/admin/products';
    const URI_ADMIN_GETPDF              = 'api/admin/getpdf';
    const URI_USER_EDITOR               = 'user/editor/editor';
    const URI_USER_DRAFTEDITOR          = 'editor';
    const URI_CUSTOMER_ORDERED          = 'api/customer/setdraftordered';
    const URI_CUSTOMER_DRAFTIMG         = 'api/customer/draftimage';
    const URI_CUSTOMER_DELETE           = 'api/customer/delete';
    const URI_CUSTOMER_DRAFT            = 'api-ext/draft';
    const URI_CUSTOMER_USER             = 'api-ext/user';
    const URI_ADMIN_GETDRAFT            = 'api/admin/getdraft';
    const URI_CUSTOMER_PDF_PROCESSING   = 'api-ext/pdf-processing';
    const URI_CUSTOMER_CREATE_DRAFT     = 'some/path/on/server';
    const URI_CUSTOMER_AUTH             = 'auth';
    const URI_REPLICATE_DRAFT           = 'api-ext/draft/{draftId}/replicate';

    /** @var Config*/
    protected $config;

    protected $authRole = self::ROLE_USER;

    public function __construct(
        Context $context,
        Config $config
    ) {
        $this->config = $config;

        parent::__construct($context);
    }

    /**
     * @return string
     */
    protected function getApikeyParamName()
    {
        return md5(substr($this->config->getSecret(), 0, 1));
    }

    /**
     * @return string
     */
    protected function getApikey()
    {
        return md5($this->config->getSecret() . $this->config->getLicense());
    }

    /**
     * @return string
     */
    protected function getAuthkeyParamName()
    {
        return md5(substr($this->config->getSecret(), 1, 1));
    }

    /**
     * @param string $roleId
     *
     * @return string
     */
    protected function getAuthkey()
    {
        return md5($this->config->getSecret() . $this->authRole);
    }

    /**
     * @param string $roleId
     *
     * @return string
     */
    protected function getPrintformerAuth()
    {
        return implode('/', [
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEditorEntry($productId, $masterId, $draftHash, $params = [], $intent = null, $user = null)
    {
        $baseParams = [
            'master_id' => $masterId,
            'product_id' => $productId,
            'intent' => $intent,
            'user' => $user
        ];
        if ($draftHash !== null) {
            $baseParams = array_merge($baseParams, [
                'draft_id' => $draftHash
            ]);
        }
        $baseUrl = $this->_urlBuilder->getUrl('printformer/editor/open', $baseParams);

        return $baseUrl . (!empty($params) ? '?' . http_build_query($params) : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getPrintformerBaseUrl()
    {
        return $this->config->getHost();
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDraft($draftHash = null, $quoteId = null)
    {
        $urlParts = [
            $this->getPrintformerBaseUrl(),
            self::URI_CUSTOMER_DRAFT,
            !$draftHash ? '' : $draftHash
        ];

        $authParams = [
            $this->getApikeyParamName() . '=' . $this->getApikey(),
            $this->getAuthkeyParamName() . '=' . $this->getAuthkey(),
        ];

        return implode('/', $urlParts) . (!empty($authParams) ? '?' . implode('&', $authParams) : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor($draftHash, $user = null, $params = [])
    {
        $params = $params['data'];
        $urlParts = [
            $this->getPrintformerBaseUrl(),
            self::URI_USER_DRAFTEDITOR,
            $draftHash
        ];

        $this->authRole = self::ROLE_ADMIN;
        $authParams = [
            $this->getApikeyParamName() . '=' . $this->getApikey(),
            $this->getAuthkeyParamName() . '=' . $this->getAuthkey(),
        ];
        $this->authRole = self::ROLE_USER;

        $urlParams = [];
        foreach ($params as $key => $value) {
            $urlParams[] = $key . '=' . $value;
        }

        $authParams = array_merge($authParams, $urlParams);

        return implode('/', $urlParts) . (!empty($authParams) ? '?' . implode('&', $authParams) : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuth()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftProcessing($draftHashes = null, $quoteId = null)
    {
        $this->authRole = self::ROLE_ADMIN;
        $urlParts = [
            $this->getPrintformerBaseUrl(),
            self::URI_CUSTOMER_ORDERED,
            $this->getPrintformerAuth()
        ];
        $this->authRole = self::ROLE_USER;

        $data = [
            $draftHashes,
            md5($quoteId)
        ];

        return implode('/', $urlParts) . '?cartdata=' . urlencode(json_encode($data));
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnail($draftHash)
    {
        $urlParts = [
            $this->getPrintformerBaseUrl(),
            self::URI_CUSTOMER_DRAFTIMG,
            $this->getPrintformerAuth(),
            'risscw2pdraftid',
            $draftHash
        ];
        return implode('/', $urlParts);
    }

    /**
     * {@inheritdoc}
     */
    public function getPDF($draftHash, $quoteId = null, $storeId = false, $websiteId = false)
    {
        return $this->getAdminPDF($draftHash, $quoteId, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewPDF($draftHash, $quoteId = null, $storeId = false, $websiteId = false)
    {
        return $this->getAdminPDF($draftHash, $quoteId, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts()
    {
        return $this->getAdminProducts();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminProducts()
    {
        $this->authRole = self::ROLE_ADMIN;
        $urlParts = [
            $this->getPrintformerBaseUrl(),
            self::URI_ADMIN_PRODUCTS,
            $this->getPrintformerAuth()
        ];
        $this->authRole = self::ROLE_USER;

        return implode('/', $urlParts);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPDF($draftHash, $quoteId, $storeId)
    {
        $this->authRole = self::ROLE_ADMIN;
        $urlParts = [
            $this->getPrintformerBaseUrl(),
            self::URI_ADMIN_GETPDF,
            $this->getPrintformerAuth(),
            'risscw2pdraft',
            md5($quoteId) . $draftHash
        ];
        $this->authRole = self::ROLE_USER;

        return implode('/', $urlParts);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPreviewPDF($draftHash, $quoteId, $storeId)
    {
        return $this->getAdminPDF($draftHash, $quoteId, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminEditor($draftHash, array $params = null, $referrer = null)
    {
        $this->authRole = self::ROLE_ADMIN;
        $url = $this->getEditor($draftHash, null, $params);
        $this->authRole = self::ROLE_USER;
        return $url .
            (strpos($url, '?') ? '&amp;' : '?') .
            'custom_referrer=' . urlencode($referrer);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminDraft($draftHash, $quoteId)
    {
        $this->authRole = self::ROLE_ADMIN;
        $url = $this->getDraft($draftHash, $quoteId);
        $this->authRole = self::ROLE_USER;

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftDelete($draftHash)
    {
        $this->authRole = self::ROLE_ADMIN;
        $urlParts = [
            $this->getPrintformerBaseUrl(),
            self::URI_CUSTOMER_DELETE,
            $this->getPrintformerAuth(),
            'risscw2pdraftid',
            $draftHash
        ];
        $this->authRole = self::ROLE_USER;

        return implode('/', $urlParts);
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirect(ProductInterface $product = null, array $redirectParams = null)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getReplicateDraftId($oldDraftId)
    {
        return $this->getPrintformerBaseUrl() . str_replace('{draftId}', $oldDraftId, self::URI_REPLICATE_DRAFT);
    }

    /**
     * @param $fileId
     *
     * @return string
     */
    public function getDerivat($fileId)
    {
        // TODO: Implement getDerivat() method.
    }

    /**
     * @param $reviewId
     *
     * @return mixed
     */
    public function getReviewPDF($reviewId)
    {
        // TODO: Implement getReviewPDF() method.
    }
}
