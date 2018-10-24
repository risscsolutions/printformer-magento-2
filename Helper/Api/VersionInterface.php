<?php
namespace Rissc\Printformer\Helper\Api;

use Magento\Catalog\Api\Data\ProductInterface;

interface VersionInterface
{
    /**
     * @param integer $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * @return integer
     */
    public function getStoreId();

    /**
     * @param int        $productId
     * @param int        $masterId
     * @param string     $draftHash
     * @param array      $params
     * @param string     $intent
     * @param int|string $user
     *
     * @return mixed
     */
    public function getEditorEntry($productId, $masterId, $draftHash, $params = [], $intent = null, $user = null);

    /**
     * @return string
     */
    public function getPrintformerBaseUrl();

    /**
     * @return string
     */
    public function getUser();

    /**
     * @param string $draftHash
     * @param int    $quoteId
     *
     * @return string
     */
    public function getDraft($draftHash = null, $quoteId = null);

    /**
     * @param string $draftHash
     * @param string $user
     * @param array  $params
     *
     * @return mixed
     */
    public function getEditor($draftHash, $user = null, $params = []);

    /**
     * @return string
     */
    public function getAuth();

    /**
     * @param array $draftHashes
     * @param int    $quoteId
     *
     * @return mixed
     */
    public function getDraftProcessing($draftHashes = [], $quoteId = null);

    /**
     * @param string $draftHash
     *
     * @return string
     */
    public function getThumbnail($draftHash);

    /**
     * @param string $draftHash
     * @param int    $quoteId
     *
     * @return string
     */
    public function getPDF($draftHash, $quoteId = null);

    /**
     * @param string $draftHash
     * @param int    $quoteId
     *
     * @return string
     */
    public function getPreviewPDF($draftHash, $quoteid = null);

    /**
     * @return string
     */
    public function getProducts();

    /**
     * @return string
     */
    public function getAdminProducts();

    /**
     * @param string $draftHash
     * @param int    $quoteId
     *
     * @return string
     */
    public function getAdminPDF($draftHash, $quoteId);

    /**
     * @param string $draftHash
     * @param int    $quoteId
     *
     * @return string
     */
    public function getAdminPreviewPDF($draftHash, $quoteId);

    /**
     * @param string $draftHash
     * @param array  $params
     * @param string $referrer
     *
     * @return string
     */
    public function getAdminEditor($draftHash, array $params = null, $referrer = null);

    /**
     * @param string $draftHash
     * @param int    $quoteId
     *
     * @return string
     */
    public function getAdminDraft($draftHash, $quoteId);

    /**
     * @param $draftHash
     *
     * @return string
     */
    public function getDraftDelete($draftHash);

    /**
     * @param ProductInterface $product
     * @param array            $redirectParams
     *
     * @return string
     */
    public function getRedirect(ProductInterface $product = null, array $redirectParams = null);

    /**
     * @param $oldDraftId
     * @return string
     */
    public function getReplicateDraftId(string $oldDraftId);
}