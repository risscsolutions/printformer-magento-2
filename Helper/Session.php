<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\Session as CustomerSession;

class Session extends AbstractHelper
{
    const SESSION_KEY_PRINTFORMER_DRAFTID = 'printformer_draftid';
    const SESSION_KEY_PRINTFORMER_CURRENT_INTENT = 'printformer_current_intent';

    /**
     * @var CatalogSession
     */
    protected $catalogSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * Session constructor.
     * @param Context $context
     * @param CatalogSession $catalogSession
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        CatalogSession $catalogSession,
        CustomerSession $customerSession
    ) {
        $this->catalogSession = $catalogSession;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @param int $productId
     * @param int $draftId
     * @param string $storeId
     * @return $this
     */
    public function setDraftId($productId, $draftId, $storeId)
    {
        $data = $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_DRAFTID);
        if (!is_array($data)) {
            $data = [];
        }
        $data[$storeId][$productId] = $draftId;
        $this->catalogSession->setData(self::SESSION_KEY_PRINTFORMER_DRAFTID, $data);

        return $this;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @return $this
     */
    public function unsDraftId($productId, $storeId)
    {
        $data = $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_DRAFTID);
        if (!is_array($data)) {
            $data = [];
        }
        unset($data[$storeId][$productId]);
        $this->catalogSession->setData(self::SESSION_KEY_PRINTFORMER_DRAFTID, $data);

        return $this;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param string $intent
     * @param bool $clear
     * @return string|null
     */
    public function getDraftId($productId, $storeId, $intent = null, $clear = false)
    {
        if($intent == $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT) || $intent == null) {
            $data = $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_DRAFTID, $clear);
            return isset($data[$storeId][$productId]) ? $data[$storeId][$productId] : null;
        } else {
            return null;
        }
    }

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @param $loginUrl
     * @return bool
     */
    public function authenticateCustomer($loginUrl = null)
    {
        return $this->customerSession->authenticate($loginUrl);
    }

    /**
     * @return CatalogSession
     */
    public function getCatalogSession()
    {
        return $this->catalogSession;
    }

    /**
     * @return CustomerSession
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }


    public function setCurrentIntent($intent)
    {
        if($intent != $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT)) {
            $this->catalogSession->setData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, $intent);
        }
    }

    public function unsetCurrentIntent()
    {
        if($this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT)) {
            $this->catalogSession->setData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, null);
        }
    }

    public function getCurrentIntent()
    {
        if($this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT)) {
            return $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT);
        }

        return null;
    }

    /**
     * @param string $draftId
     *
     * @return array|null
     */
    public function getDraftCache($draftId)
    {
        if (!$this->catalogSession->getPrintformerDraftCache()) {
            return null;
        }

        $drafts = $this->catalogSession->getPrintformerDraftCache();
        if (empty($drafts[$draftId])) {
            return null;
        }

        return $drafts[$draftId];
    }

    /**
     * @param string $draftId
     *
     * @return bool
     */
    public function hasDraftInCache($draftId)
    {
        return $this->getDraftCache($draftId) != null;
    }

    /**
     * @param $draftId
     * @param $data
     *
     * @return void
     */
    public function addDraftToCache($draftId, $data)
    {
        if ($this->hasDraftInCache($draftId)) {
            return;
        }

        $drafts = $this->catalogSession->getPrintformerDraftCache();
        if (!$drafts) {
            $drafts = [];
        }

        $drafts[$draftId] = $data;

        $this->catalogSession->setPrintformerDraftCache($drafts);
    }

    /**
     * @param $draftId
     * @param $data
     *
     * @return void
     */
    public function updateDraftInCache($draftId, $data)
    {
        if (!$this->catalogSession->getPrintformerDraftCache()) {
            return;
        }

        if (!$this->hasDraftInCache($draftId)) {
            return;
        }

        $drafts = $this->catalogSession->getPrintformerDraftCache();

        $drafts[$draftId] = $data;

        $this->catalogSession->setPrintformerDraftCache($drafts);
    }

    /**
     * @param string $draftId
     */
    public function removeDraftFromCache($draftId)
    {
        if (!$this->catalogSession->getPrintformerDraftCache()) {
            return;
        }

        if (!$this->hasDraftInCache($draftId)) {
            return;
        }

        $drafts = $this->catalogSession->getPrintformerDraftCache();
        unset($drafts[$draftId]);
        $this->catalogSession->setPrintformerDraftCache($drafts);
    }
}
