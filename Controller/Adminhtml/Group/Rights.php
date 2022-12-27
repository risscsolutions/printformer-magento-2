<?php

namespace Rissc\Printformer\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Rights extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * Show constructor.
     *
     * @param   PageFactory  $pageFactory
     * @param   Context      $context
     */
    public function __construct(
        PageFactory $pageFactory,
        Context $context
    ) {
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Magento_Backend::other_settings');
        $resultPage->getConfig()->getTitle()
            ->prepend(__('Customer Group Rights'));

        return $resultPage;
    }
}
