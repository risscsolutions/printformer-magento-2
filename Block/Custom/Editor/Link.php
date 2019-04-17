<?php
namespace Rissc\Printformer\Block\Custom\Editor;

use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order\Item as OrderItem;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer;
use Rissc\Printformer\Helper\Api\Url;
use Magento\Framework\DataObject;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Model\Draft;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\App\ObjectManager;

class Link
    extends Template
{
    /** @var Url */
    protected $_urlHelper;

    /** @var ApiHelper */
    protected $_apiHelper;

    /** @var ObjectManager */
    protected $_objManager;

    /** @var AuthorizationInterface */
    protected $_authorization;

    /**
     * Link constructor.
     *
     * @param Template\Context $context
     * @param Url              $urlHelper
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        Url $urlHelper,
        ApiHelper $apiHelper,
        AuthorizationInterface $authorization,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_urlHelper = $urlHelper;
        $this->_apiHelper = $apiHelper;
        $this->_objManager = ObjectManager::getInstance();
        $this->_authorization = $authorization;
    }

    /**
     * @return Item
     */
    public function getQuoteItem()
    {
        return $this->getData('quote_item');
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->getData('product');
    }

    /**
     * @return string
     */
    public function getEditorOpenLink()
    {
        return $this->getData('editor_link');
    }

    /**
     * @return Printformer
     */
    public function getPrintformerBlock()
    {
        return $this->getData('printformer_block');
    }

    /**
     * @param $draftID
     *
     * @return string
     */
    public function getDraftURL($draftID) {
        return $this->_urlBuilder->getUrl('printformer/drafts/index', ['filter' => base64_encode('draft_id='.$draftID)]);
    }

    /**
     * @param DataObject $item
     *
     * @return string
     */
    public function getPdfUrl(DataObject $item, $draftHash)
    {
        return $this->_urlHelper->setStoreId($item->getOrder()->getStoreId())
            ->getAdminPdf($draftHash, $item->getOrder()->getQuoteId());
    }

    /**
     * @param DataObject $item
     *
     * @return string
     */
    public function getPreviewPdfUrl(DataObject $item, $draftHash)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminPreviewPDF($draftHash, $item->getOrder()->getQuoteId());
    }

    /**
     * @param $draftHash
     *
     * @return \Rissc\Printformer\Model\Draft
     * @throws \Exception
     */
    public function getDraftProcess($draftHash)
    {
        return $this->_apiHelper->draftProcess($draftHash);
    }

    /**
     * @param OrderItem $orderItem
     * @param string    $draftHash
     *
     * @return bool
     * @throws \Exception
     */
    public function isOrdered(OrderItem $orderItem, $draftHash)
    {
        if($orderItem->getPrintformerOrdered()) {
            return true;
        }

        /** @var Draft $draftProcess */
        $draftProcess = $this->getDraftProcess($draftHash);
        if(!$draftProcess->getId()) {
            return false;
        }

        if($draftProcess->getProcessingStatus() == 1) {
            $orderItem->setPrintformerOrdered(1);
            $orderItem->getResource()->save($orderItem);

            return true;
        }

        return false;
    }

    public function canViewHighRes()
    {
        /** @var \Magento\Framework\App\State $appState */
        $appState = $this->_objManager->get('Magento\Framework\App\State');
        if ($appState->getAreaCode() !== 'adminhtml') {
            return true;
        }

        return $this->_authorization->isAllowed('Rissc_Printformer::view_highres_pdf');
    }
}