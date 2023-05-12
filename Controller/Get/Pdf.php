<?php

namespace Rissc\Printformer\Controller\Get;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Model\ResourceModel\Draft\CollectionFactory as DraftCollectionFactory;
use Rissc\Printformer\Model\Draft as DraftModel;

class Pdf extends Action
{
    /** @var Api */
    protected $_apiHelper;

    /** @var DraftCollectionFactory */
    protected $_draftCollectionFactory;

    /**
     * Pdf constructor.
     * @param Context $context
     * @param DraftCollectionFactory $draftCollectionFactory
     * @param Api $apiHelper
     */
    public function __construct(
        Context $context,
        DraftCollectionFactory $draftCollectionFactory,
        Api $apiHelper
    ) {
        $this->_apiHelper = $apiHelper;
        $this->_draftCollectionFactory = $draftCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Exception
     */
    public function execute()
    {
        $draftId = $this->getRequest()->getParam('draft_id');
        $quoteId = $this->getRequest()->getParam('quote_id');

        /** @var DraftModel $draft */
        $draft = $this->_apiHelper->draftProcess($draftId);

        if ($draftId == $draft->getDraftId()) {
            $url = $this->_apiHelper->apiUrl()->getAdminPdf($draft->getDraftId(), $quoteId, $draft->getStoreId());
            $this->_redirect($url);
            return;
        }

        $this->_redirect($this->_url->getUrl('/'));
    }
}
