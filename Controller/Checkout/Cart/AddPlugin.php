<?php

namespace Rissc\Printformer\Controller\Checkout\Cart;

use Magento\Checkout\Controller\Cart\Add;

class AddPlugin
{
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
