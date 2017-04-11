<?php
namespace Rissc\Printformer\Controller\Checkout\Cart;

class AddPlugin
{
    /**
     * @param \Magento\Checkout\Controller\Cart\Add $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Checkout\Controller\Cart\Add $subject, $result)
    {
        $rerirectUrl = $subject->getRequest()->getParam('redirect_url');
        if ($rerirectUrl) {
            $result->setUrl($rerirectUrl);
        }

        return $result;
    }
}
