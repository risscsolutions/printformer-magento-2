<?php
namespace Rissc\Printformer\Controller\Get;

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

    public function __construct(Context $context,
                                CustomerSession $_customerSession)
    {
        $this->_customerSession = $_customerSession;

        parent::__construct($context);
    }

    public function execute()
    {
        if($this->_request->getParam('delete')) {
            $this->_customerSession->setSavedEditorCalls(null);
            exit();
        }

        $_savedSessionData = $this->_customerSession->getSavedEditorCalls();
        if($_savedSessionData !== null)
        {
            echo json_encode($_savedSessionData);
        }
        exit();
    }
}