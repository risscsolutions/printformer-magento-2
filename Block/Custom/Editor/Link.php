<?php
namespace Rissc\Printformer\Block\Custom\Editor;

use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote\Item;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer;
use Rissc\Printformer\Helper\Url;
use Magento\Framework\DataObject;

class Link
    extends Template
{
    /** @var Url */
    protected $_urlHelper;

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
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_urlHelper = $urlHelper;
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
    public function getPdfUrl(DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminPdfUrl($item->getPrintformerDraftid(), $item->getOrder()->getQuoteId());
    }
}