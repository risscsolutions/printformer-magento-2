<?php
namespace Rissc\Printformer\Plugin\Block\Printformer;

use Rissc\Printformer\Block\Catalog\Product\View\Printformer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\Session as CatalogSession;
use Rissc\Printformer\Controller\Editor\Save;
use Rissc\Printformer\Helper\Session;

class Plugin
{
    /** @var CustomerSession */
    protected $_customerSession;

    /** @var CatalogSession */
    protected $_catalogSession;

    /**
     * Plugin constructor.
     *
     * @param CustomerSession $customerSession
     * @param CatalogSession  $catalogSession
     */
    public function __construct(CustomerSession $customerSession, CatalogSession $catalogSession)
    {
        $this->_customerSession = $customerSession;
        $this->_catalogSession = $catalogSession;
    }
    public function afterToHtml(Printformer $subject, $resultHtml)
    {
        if ($subject->isOnConfigurePDS()) {
            if ($this->_catalogSession->getSavedPrintformerOptions()) {
                $this->_catalogSession->setSavedPrintformerOptions(null);
            }
            if ($this->_catalogSession->getData(Save::PERSONALISATIONS_QUERY_PARAM)) {
                $this->_catalogSession->setData(Save::PERSONALISATIONS_QUERY_PARAM, null);
            }
            if ($this->_catalogSession->getData(Session::SESSION_KEY_PRINTFORMER_CURRENT_INTENT)) {
                $this->_catalogSession->setData(Session::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, null);
            }
            if ($this->_customerSession->getSessionUniqueId()) {
                $this->_customerSession->setSessionUniqueId(null);
            }
        }

        return $resultHtml;
    }
}