<?php
namespace Rissc\Printformer\Controller\Get;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Action\Action;
use \Magento\Customer\Model\Session as CustomerSession;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Draft as PfDraft;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;

/**
 * Class Draft
 * @package Rissc\Printformer\Controller\Get
 */
class Draft
    extends Action
{
    /** @var CustomerSession */
    protected $_customerSession;

    /** @var DraftFactory */
    protected $_draftFactory;


    protected $_productFactory;

    public function __construct(
        Context $context,
        CustomerSession $_customerSession,
        DraftFactory $draftFactory,
        ProductFactory $productFactory
    )
    {
        $this->_customerSession = $_customerSession;
        $this->_draftFactory = $draftFactory;
        $this->_productFactory = $productFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product');
        $uniqueID = $this->_customerSession->getSessionUniqueID();
        if(!$uniqueID)
        {
            exit();
        }
        else if ($uniqueID != null)
        {
            $uniqueIdExplode = explode(':', $uniqueID);
            if(isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] != $productId)
            {
                exit();
            }
        }

        /** @var PfDraft $draft */
        $draft = $this->_draftFactory->create();
        $draftCollection = $draft->getCollection()
            ->addFieldToFilter('session_unique_id', ['eq' => $uniqueID]);


        if($this->_request->getParam('delete'))
        {
            /** @var PfDraft $draftItem */
            foreach($draftCollection->getItems() as $draftItem)
            {
                if($this->_request->getParam('excludeDraft') && $this->_request->getParam('excludeDraft') != $draftItem->getDraftId())
                {
                    $draftItem->getResource()->delete($draftItem);
                }
            }
            $this->_customerSession->setSessionUniqueID(null);
            exit();
        }

        $savedSessionData = null;
        /** @var Product $product */
        $product = null;
        foreach($draftCollection->getItems() as $draftItem)
        {
            if(!$product)
            {
                $product = $this->_productFactory->create();
                $product->getResource()->load($product, $draftItem->getProductId());
                if(!$product->getId())
                {
                    $product = null;
                }
            }

            if($product && $product->getId())
            {
                $savedSessionData[$draftItem->getIntent()] = [
                    'master_id' => $product->getPrintformerProduct(),
                    'draft_id' => $draftItem->getDraftId(),
                    'intent' => $draftItem->getIntent()
                ];
            }
        }

        if($savedSessionData !== null)
        {
            echo json_encode($savedSessionData);
        }
        exit();
    }
}