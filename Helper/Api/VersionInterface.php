<?php
namespace Rissc\Printformer\Helper\Api;

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
     *
     * @return string
     */
    public function getDraft($draftHash = null);

    /**
     * @param       $draftHash
     * @param array $params
     *
     * @return string
     */
    public function getEditor($draftHash, $params = []);

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
     *
     * @return string
     */
    public function getPDF($draftHash);

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
}