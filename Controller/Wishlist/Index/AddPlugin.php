<?php

namespace Rissc\Printformer\Controller\Wishlist\Index;

use Magento\Framework\App\RequestInterface;
use Magento\Wishlist\Controller\Index\Add;
use Rissc\Printformer\Model\PrintformerPlugin;

class AddPlugin extends PrintformerPlugin
{
    /**
     * Add wishlist hint text for guest users
     * @param Add $subject
     * @param RequestInterface $request
     */
    public function beforeDispatch(Add $subject, RequestInterface $request)
    {
        if ($subject->getActionFlag()->get('', 'no-dispatch')) {
            $this->messageManager->addNoticeMessage($this->config->getGuestWishlistHint());
        }
    }

    /**
     * @param Add $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(Add $subject, $result)
    {
        $rerirectUrl = $subject->getRequest()->getParam('redirect_url');
        if ($rerirectUrl) {
            $result->setUrl($rerirectUrl);
        }

        return $result;
    }
}
