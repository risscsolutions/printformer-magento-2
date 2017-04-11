<?php
namespace Rissc\Printformer\Controller\Wishlist\Index;

use Magento\Framework\App\RequestInterface;

/**
 * Class AddPlugin
 * @package Rissc\Printformer\Controller\Wishlist\Index
 */
class AddPlugin extends \Rissc\Printformer\Model\PrintformerPlugin
{
    /**
     * Add wishlist hint text for guest users
     *
     * @param \Magento\Wishlist\Controller\Index\Add $subject
     * @param RequestInterface $request
     * @return void
     */
    public function beforeDispatch(\Magento\Wishlist\Controller\Index\Add $subject, RequestInterface $request)
    {
        if ($subject->getActionFlag()->get('', 'no-dispatch')) {
            $this->messageManager->addNoticeMessage($this->config->getGuestWishlistHint());
        }
    }

    /**
     * @param \Magento\Wishlist\Controller\Index\Add $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Wishlist\Controller\Index\Add $subject, $result)
    {
        $rerirectUrl = $subject->getRequest()->getParam('redirect_url');
        if ($rerirectUrl) {
            $result->setUrl($rerirectUrl);
        }

        return $result;
    }
}
