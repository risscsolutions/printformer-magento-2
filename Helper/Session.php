<?php
namespace Rissc\Printformer\Helper;

class Session extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SESSION_KEY_PRINTFORMER_DRAFTID = 'printformer_draftid';

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
     * @param integer $productId
     * @param integer $storeId
     * @param boolean $clear
     * @return string
     */
    public function getDraftId($productId, $storeId, $clear = false)
    {
        $data = $this->catalogSession->getData(self::SESSION_KEY_PRINTFORMER_DRAFTID, $clear);
        return isset($data[$storeId][$productId]) ? $data[$storeId][$productId] : null;
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
}
