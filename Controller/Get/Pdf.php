<?php

namespace Rissc\Printformer\Controller\Get;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Model\ResourceModel\Draft\CollectionFactory as DraftCollectionFactory;
use Rissc\Printformer\Model\Draft as DraftModel;

/**
 *
 */
class Pdf extends Action
{
    /** @var Api */
    protected $_apiHelper;

    /** @var DraftCollectionFactory */
    protected $_draftCollectionFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * Pdf constructor.
     * @param Context $context
     * @param DraftCollectionFactory $draftCollectionFactory
     * @param Api $apiHelper
     * @param ManagerInterface $messageManager
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        DraftCollectionFactory $draftCollectionFactory,
        Api $apiHelper,
        ManagerInterface $messageManager,
        CustomerSession $customerSession
    ) {
        $this->_apiHelper = $apiHelper;
        $this->_draftCollectionFactory = $draftCollectionFactory;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
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

        //Customer check
        if ((int)$draft->getCustomerId() !== (int)$this->customerSession->getCustomerId()
        ) {
            $this->messageManager->addErrorMessage(__('You are not allowed to download this file.'));
            $this->_redirect($this->_url->getUrl('/'));
        }
        if ($draftId == $draft->getDraftId()) {
            $url = $this->_apiHelper->apiUrl()->getAdminPdf($draft->getDraftId(), $quoteId, $draft->getStoreId());
            $this->_redirect($url);
            return;
        }

        $this->_redirect($this->_url->getUrl('/'));
    }
}
