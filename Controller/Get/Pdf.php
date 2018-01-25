<?php
namespace Rissc\Printformer\Controller\Get;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Rissc\Printformer\Gateway\User\Draft;
use Rissc\Printformer\Helper\Url;
use Rissc\Printformer\Model\ResourceModel\Draft\Collection;
use Rissc\Printformer\Model\ResourceModel\Draft\CollectionFactory as DraftCollectionFactory;

class Pdf extends Action
{
    /** @var Draft */
    protected $_draftHelper;

    /** @var Url */
    protected $_urlHelper;

    /** @var DraftCollectionFactory */
    protected $_draftCollectionFactory;

    public function __construct(
        Context $context,
        DraftCollectionFactory $draftCollectionFactory,
        Draft $draft,
        Url $url
    ){
        $this->_draftHelper = $draft;
        $this->_urlHelper = $url;
        $this->_draftCollectionFactory = $draftCollectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $draftId = $this->getRequest()->getParam('draft_id');
        $quoteId = $this->getRequest()->getParam('quote_id');
        $isAsync = false;
        /** @var Collection $draftCollection */
        $draftCollection = $this->_draftCollectionFactory->create();
        $draftItem = $draftCollection->addFieldToFilter('draft_id', ['eq' => $draftId]);
        if($draftItem->count() == 1) {
            $draft = $draftItem->getFirstItem();
            if($draft->getProcessingId() != null) {
                $isAsync = true;
                $quoteId = $draft->getProcessingId();
            }
            //API V1 url
            $url = $this->_urlHelper->getAdminPdfUrl($draftId, $quoteId, $isAsync);
            $this->_redirect($url);
        }
    }
}