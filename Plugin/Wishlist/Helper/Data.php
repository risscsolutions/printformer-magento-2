<?php

namespace Rissc\Printformer\Plugin\Wishlist\Helper;

use Magento\Framework\App\Request\Http;
use Magento\Wishlist\Helper\Data as SubjectData;

class Data
{
    private Http $request;

    /**
     * @param Http $request
     */
    public function __construct(Http $request)
    {
        $this->request = $request;
    }

    /**
     * @param SubjectData $subject
     * @param callable $proceed
     * @param $item
     * @param array $params
     * @return mixed
     */
    public function aroundGetAddParams(SubjectData $subject, Callable $proceed, $item, array $params = [])
    {
        if ($this->request->getModuleName() == 'checkout' && $this->request->getActionName() == 'configure') {
            $requestParams = $this->request->getParams();
            if (isset($requestParams['id'])) {
                $itemId = $requestParams['id'];
                $params['item'] = $itemId;
            }
        }

        $result = $proceed($item, $params);

        return $result;
    }
}