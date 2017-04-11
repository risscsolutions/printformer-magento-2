<?php
namespace Rissc\Printformer\Model;

class PrintformerPlugin
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Rissc\Printformer\Model\DraftFactory
     */
    protected $draftFactory;

    /**
     * @var \Rissc\Printformer\Gateway\Admin\Draft
     */
    protected $printformerDraft;

    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $config;

    /**
     * @var \Rissc\Printformer\Helper\Session
     */
    protected $sessionHelper;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Registry $coreRegistry
     * @param DraftFactory $draftFactory
     * @param \Rissc\Printformer\Gateway\Admin\Draft $printformerDraft
     * @param \Rissc\Printformer\Helper\Config $config
     * @param \Rissc\Printformer\Helper\Session $sessionHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $coreRegistry,
        \Rissc\Printformer\Model\DraftFactory $draftFactory,
        \Rissc\Printformer\Gateway\Admin\Draft $printformerDraft,
        \Rissc\Printformer\Helper\Config $config,
        \Rissc\Printformer\Helper\Session $sessionHelper

    ) {
        $this->logger                  = $logger;
        $this->config                  = $config;
        $this->draftFactory            = $draftFactory;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->printformerDraft        = $printformerDraft;
        $this->storeManager            = $storeManager;
        $this->messageManager          = $messageManager;
        $this->sessionHelper           = $sessionHelper;
        $this->coreRegistry            = $coreRegistry;
    }
}
