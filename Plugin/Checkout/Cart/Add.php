<?php

namespace Rissc\Printformer\Plugin\Checkout\Cart;

use Magento\Checkout\Controller\Cart\Add as SubjectAdd;

class Add
{
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