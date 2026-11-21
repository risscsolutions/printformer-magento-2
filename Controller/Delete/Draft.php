<?php

namespace Rissc\Printformer\Controller\Delete;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ResourceConnection;
use Rissc\Printformer\Helper\Session;

/**
 * Deletes a draft.
 *
 * - POST only (HttpPostActionInterface). Because this class does NOT implement
 *   CsrfAwareActionInterface, Magento's CsrfValidator automatically validates
 *   the `form_key` for every request.
 * - Verifies that the draft belongs to the current customer / guest session.
 * - Uses bound parameters instead of string concatenation (SQL injection).
 */
class Draft extends Action implements HttpPostActionInterface
{
    /** @var ProductFactory */
    protected $_productFactory;

    /** @var ProductResource */
    protected $_productResource;

    /** @var ResourceConnection */
    protected $_resourceConnection;

    /** @var Session */
    protected $_sessionHelper;

    /** @var RedirectFactory */
    protected $_redirectFactory;

    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        ProductResource $productResource,
        ResourceConnection $resourceConnection,
        Session $sessionHelper,
        RedirectFactory $redirectFactory
    ) {
        $this->_productFactory     = $productFactory;
        $this->_productResource    = $productResource;
        $this->_resourceConnection = $resourceConnection;
        $this->_sessionHelper      = $sessionHelper;
        $this->_redirectFactory    = $redirectFactory;

        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $request   = $this->getRequest();
        $resultUrl = $this->_redirectFactory->create();

        $productId = (int)$request->getParam('product_id');
        /** @var Product $product */
        $product = $this->_productFactory->create();
        $this->_productResource->load($product, $productId);

        $backUrl = $product->getId() ? $product->getProductUrl() : $this->_url->getUrl('/');

        $selectedProductId   = (int)$request->getParam('selected_product_id');
        $draftId             = trim((string)$request->getParam('selected_product_draft_id'));
        $printformerProduct  = $request->getParam('printformer_product');

        if ($draftId === '' || !$selectedProductId || $printformerProduct === null || $printformerProduct === '') {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $resultUrl->setUrl($backUrl);
        }

        if (!$this->isDraftOwnedByCurrentUser($draftId, $selectedProductId, $printformerProduct)) {
            // Same message for "not found" and "not yours" - do not leak existence of drafts.
            $this->messageManager->addErrorMessage(__('Draft could not be deleted.'));
            return $resultUrl->setUrl($backUrl);
        }

        $connection = $this->_resourceConnection->getConnection();
        $connection->delete(
            $this->_resourceConnection->getTableName('printformer_draft'),
            ['draft_id = ?' => (string)$draftId]
        );

        $this->_sessionHelper->unsetCurrentIntent();

        $customerSession  = $this->_sessionHelper->getCustomerSession();
        $sessionUniqueIds = $customerSession->getSessionUniqueIds();
        if (isset($sessionUniqueIds[$selectedProductId][$printformerProduct])) {
            unset($sessionUniqueIds[$selectedProductId][$printformerProduct]);
            $customerSession->setData('session_unique_ids', $sessionUniqueIds);
        }

        $this->messageManager->addSuccessMessage(__('Draft has been successfully deleted.'));

        return $resultUrl->setUrl($backUrl);
    }

    /**
     * Logged-in customer: draft.customer_id must equal the customer id.
     * Guest: draft must have no customer and its session_unique_id must match
     * the one stored in the current session for this product/template.
     *
     * NOTE: adjust column names if your printformer_draft table differs.
     *
     * @param string     $draftId
     * @param int        $productId
     * @param string|int $printformerProductId
     * @return bool
     */
    protected function isDraftOwnedByCurrentUser($draftId, $productId, $printformerProductId)
    {
        $connection = $this->_resourceConnection->getConnection();
        $table      = $this->_resourceConnection->getTableName('printformer_draft');

        $row = $connection->fetchRow(
            $connection->select()
                ->from($table, ['draft_id', 'customer_id', 'session_unique_id'])
                ->where('draft_id = ?', $draftId)
                ->limit(1)
        );

        if (!$row) {
            return false;
        }

        $customerSession = $this->_sessionHelper->getCustomerSession();

        if ($customerSession->isLoggedIn()) {
            return (int)$row['customer_id'] > 0
                && (int)$row['customer_id'] === (int)$customerSession->getCustomerId();
        }

        // Guest
        if (!empty($row['customer_id'])) {
            return false;
        }

        $sessionUniqueIds = $customerSession->getSessionUniqueIds();
        $currentUniqueId  = $sessionUniqueIds[$productId][$printformerProductId] ?? null;

        return !empty($currentUniqueId)
            && !empty($row['session_unique_id'])
            && hash_equals((string)$row['session_unique_id'], (string)$currentUniqueId);
    }
}
