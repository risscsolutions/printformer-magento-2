<?php
namespace Rissc\Printformer\Observer\Checkout\Cart\Add;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer as EventObserver;
use \Magento\Customer\Model\Session as CustomerSession;
use \Magento\Catalog\Model\Session as CatalogSession;
use \Rissc\Printformer\Controller\Editor\Save;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Helper\Config;

class Observer
    implements ObserverInterface
{
    /** @var CustomerSession */
    protected $_customerSession;

    /** @var CatalogSession */
    protected $_catalogSession;
    private Session $sessionHelper;
    private Config $configHelper;

    /**
     * Observer constructor.
     *
     * @param CustomerSession $_customerSession
     * @param CatalogSession $_catalogSession
     * @param Session $sessionHelper
     */
    public function __construct(
        CustomerSession $_customerSession,
        CatalogSession $_catalogSession,
        Session $sessionHelper,
        Config $configHelper
    )
    {
        $this->_customerSession = $_customerSession;
        $this->_catalogSession = $_catalogSession;
        $this->sessionHelper = $sessionHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $this->_catalogSession->setSavedPrintformerOptions(null);
        $this->_catalogSession->setData(Save::PERSONALISATIONS_QUERY_PARAM, null);
        $this->_catalogSession->setData(Session::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, null);
        $product = $observer->getData('product');
        if (isset($product)){
            $productItem = $observer->getQuoteItem();
            if ($productItem->getPrintformerDraftid()) {
                $draftKey = Session::SESSION_DRAFT_KEY . $productItem->getPrintformerDraftid();
                $this->_catalogSession->setData($draftKey, null);
            }
            if ($this->configHelper->useChildProduct($productItem->getProductType())) {
                $quoteChildren = $productItem->getChildren();
                if (is_array($quoteChildren) && !empty($quoteChildren) && !empty($quoteChildren[0])){
                    $firstProduct = $quoteChildren[0]['product'];
                    if (!empty($firstProduct)) {
                        $productId = $firstProduct->getData('entity_id');
                    }
                }
            } else {
                $productId = $product->getData('entity_id');
            }
            if (isset($productId)) {
                $this->sessionHelper->removeSessionUniqueIdFromSession($productId);
            }
        }

    }
}
