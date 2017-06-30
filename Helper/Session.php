<?php
namespace Rissc\Printformer\Helper;

class Session extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SESSION_KEY_PRINTFORMER_DRAFTID = 'printformer_draftid';
    const SESSION_KEY_PRINTFORMER_CURRENT_INTENT = 'printformer_current_intent';

    /**
     * Catalog session
     *
     * @var \Magento\Catalog\Model\Session
     */
    protected $catalogSession;

    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->catalogSession = $catalogSession;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @param integer $productId
     * @param integer $storeId
     * @param string $draftId
     * @return \Rissc\Printformer\Helper\Session
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
     * @param integer $productId
     * @param integer $storeId
     * @return \Rissc\Printformer\Helper\Session
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
     * @param      $productId
     * @param      $storeId
     * @param      $intent
     * @param bool $clear
     *
     * @return null
     */
    public function getDraftId($productId, $storeId, $intent = null, $clear = false)
    {
        if($intent == $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT) || $intent == null)
        {
            $data = $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_DRAFTID, $clear);
            return isset($data[$storeId][$productId]) ? $data[$storeId][$productId] : null;
        }
        else
        {
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

    public function authenticateCustomer($loginUrl = null)
    {
        return $this->customerSession->authenticate($loginUrl);
    }

    /**
     * @return \Magento\Catalog\Model\Session
     */
    public function getCatalogSession() {
        return $this->catalogSession;
    }

    /**
     * @return \Magento\Customer\Model\Session
     */
    public function getCustomerSession() {
        return $this->customerSession;
    }


    public function setCurrentIntent($intent)
    {
        if($intent != $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT))
        {
            $this->catalogSession->setData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, $intent);
        }
    }

    public function unsetCurrentIntent()
    {
        if($this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT))
        {
            $this->catalogSession->setData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, null);
        }
    }

    public function getCurrentIntent()
    {
        if($this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT))
        {
            return $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_CURRENT_INTENT);
        }

        return null;
    }
}
