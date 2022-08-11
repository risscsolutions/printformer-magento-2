<?php
namespace Rissc\Printformer\Observer\Checkout\Cart\Add;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer as EventObserver;
use \Magento\Customer\Model\Session as CustomerSession;
use \Magento\Catalog\Model\Session as CatalogSession;
use \Rissc\Printformer\Controller\Editor\Save;
use Rissc\Printformer\Helper\Session;

class Observer
    implements ObserverInterface
{
    /** @var CustomerSession */
    protected $_customerSession;

    /** @var CatalogSession */
    protected $_catalogSession;
    private Session $sessionHelper;

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
        Session $sessionHelper
    )
    {
        $this->_customerSession = $_customerSession;
        $this->_catalogSession = $_catalogSession;
        $this->sessionHelper = $sessionHelper;
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
            if ($quoteChildren = $observer->getQuoteItem()->getChildren()) {
                $productId = $quoteChildren[0]['product']->getData('entity_id');
            } else {
                $productId = $product->getData('entity_id');
            }
            if (isset($productId)) {
                $this->sessionHelper->removeSessionUniqueIdFromSession($productId);
            }
        }

    }
}