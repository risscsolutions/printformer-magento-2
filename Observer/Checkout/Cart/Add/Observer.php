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

    public function __construct(
        CustomerSession $_customerSession,
        CatalogSession $_catalogSession
    )
    {
        $this->_customerSession = $_customerSession;
        $this->_catalogSession = $_catalogSession;
    }

    public function execute(EventObserver $observer)
    {
        $this->_customerSession->setSavedPrintformerOptions(null);
        $this->_catalogSession->setData(Save::PERSONALISATIONS_QUERY_PARAM, null);
        $this->_catalogSession->setData(Session::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, null);
        $this->_customerSession->setSessionUniqueID(null);
    }
}