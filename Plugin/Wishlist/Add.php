<?php

namespace Rissc\Printformer\Plugin\Wishlist;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Wishlist\Controller\Index\Add as SubjectAdd;
use Rissc\Printformer\Helper\Config;

class Add
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Add constructor.
     * @param ManagerInterface $messageManager
     * @param Config $config
     */
    public function __construct(
        ManagerInterface $messageManager,
        Config $config
    ) {
        $this->messageManager = $messageManager;
        $this->config = $config;
    }

    /**
     * Add wishlist hint text for guest users
     * @param SubjectAdd $subject
     * @param RequestInterface $request
     */
    public function beforeDispatch(SubjectAdd $subject, RequestInterface $request)
    {
        if ($subject->getActionFlag()->get('', 'no-dispatch')) {
            $this->messageManager->addNoticeMessage($this->config->getGuestWishlistHint());
        }
    }

    /**
     * @param SubjectAdd $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function afterExecute(SubjectAdd $subject, $result)
    {
        $rerirectUrl = $subject->getRequest()->getParam('redirect_url');
        if ($rerirectUrl) {
            $result->setUrl($rerirectUrl);
        }

        return $result;
    }
}