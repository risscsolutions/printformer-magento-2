<?php

namespace Rissc\Printformer\Plugin\Wishlist;

use Magento\Framework\Registry;
use Magento\Wishlist\Controller\Index\UpdateItemOptions as SubjectUpdateItemOptions;

class UpdateItemOptions
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * UpdateItemOptions constructor.
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * @param SubjectUpdateItemOptions $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(SubjectUpdateItemOptions $subject, $result)
    {
        $redirectUrl = $subject->getRequest()->getParam('redirect_url');
        $newItemId = $this->registry->registry(\Rissc\Printformer\Helper\Config::REGISTRY_KEY_WISHLIST_NEW_ITEM_ID);
        if ($redirectUrl && $newItemId) {
            $redirectUrl = $redirectUrl . 'id/' . $newItemId . '/';
            $result->setUrl($redirectUrl);
        }

        return $result;
    }
}