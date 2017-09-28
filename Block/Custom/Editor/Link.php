<?php
namespace Rissc\Printformer\Block\Custom\Editor;

use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote\Item;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer;
use Magento\Framework\UrlInterface;
use Rissc\Printformer\Helper\Url;
use \Magento\Framework\DataObject;

class Link
    extends Template
{

    /** @var UrlInterface  */
    protected $_url;

    protected $_urlHelper;

    /**
     * Link constructor.
     * @param Template\Context $context
     * @param UrlInterface $url
     * @param array $data
     */
    public function __construct(Template\Context $context,
                                UrlInterface $url,
                                Url $urlHelper,
                                array $data = [])
    {
        parent::__construct($context, $data);
        $this->_url = $url;
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
     * @return string
     */
    public function getDraftURL($draftID) {
        return $this->_url->getUrl('printformer/drafts/index', ['filter' => base64_encode('draft_id='.$draftID)]);
    }

    /**
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    public function getPdfUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminPdfUrl($item->getPrintformerDraftid(), $item->getOrder()->getQuoteId());
    }
}