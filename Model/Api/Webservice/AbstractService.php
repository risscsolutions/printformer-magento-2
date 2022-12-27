<?php

namespace Rissc\Printformer\Model\Api\Webservice;

use Magento\Framework\Webapi\Rest\Request;

class AbstractService
{
    /** @var Request */
    protected $_request;

    public function __construct(
        Request $_request
    ) {
        $this->_request = $_request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }
}
