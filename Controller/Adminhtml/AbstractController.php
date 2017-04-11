<?php
namespace Rissc\Printformer\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

abstract class AbstractController
    extends Action
{
    /** @var PageFactory */
    protected $_resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $_resultPageFactory
    )
    {
        $this->_resultPageFactory = $_resultPageFactory;

        parent::__construct($context);
    }


    /* (non-PHPdoc)
     * @see \Magento\Backend\App\AbstractAction::_isAllowed()
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Rissc_Printformer::config');
    }
}