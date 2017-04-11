<?php
namespace Rissc\Printformer\Controller\Wishlist\Index;

class UpdateItemOptionsPlugin extends \Rissc\Printformer\Model\PrintformerPlugin
{
    /**
     * @param \Magento\Wishlist\Controller\Index\UpdateItemOptions $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Wishlist\Controller\Index\UpdateItemOptions $subject, $result)
    {
        $redirectUrl = $subject->getRequest()->getParam('redirect_url');
        $newItemId = $this->coreRegistry
            ->registry(\Rissc\Printformer\Helper\Config::REGISTRY_KEY_WISHLIST_NEW_ITEM_ID);
        if ($redirectUrl && $newItemId) {
            $redirectUrl = $redirectUrl . 'id/' . $newItemId . '/';
            $result->setUrl($redirectUrl);
        }

        return $result;
    }
}
