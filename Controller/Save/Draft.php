<?php
namespace Rissc\Printformer\Controller\Save;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Action\Action;
use \Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Draft
 * @package Rissc\Printformer\Controller\Get
 */
class Draft
    extends Action
{
    /** @var CustomerSession */
    protected $_customerSession;

    /**
     * Draft constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session       $_customerSession
     */
    public function __construct(
        Context $context,
        CustomerSession $_customerSession
    )
    {
        $this->_customerSession = $_customerSession;

        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if(
            !isset($params['draft_type']) ||
            !isset($params['master_id']) ||
            !isset($params['draft_id'])
        )
        {
            exit();
        }
        $_savedSessionData = $this->_customerSession->getSavedEditorCalls();

        $_savedSessionData[$params['draft_type']] = [
            'master_id' => $params['master_id'],
            'draft_id' => $params['draft_id']
        ];

        $this->_customerSession->setSavedEditorCalls($_savedSessionData);
        exit();
    }
}