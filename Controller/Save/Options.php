<?php
namespace Rissc\Printformer\Controller\Save;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Action\Action;
use \Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Options
 * @package Rissc\Printformer\Controller\Get
 */
class Options
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
        $params = $this->getRequest()->getParam('product_options');

        $this->_customerSession->setSavedPrintformerOptions($params);
        exit();
    }
}