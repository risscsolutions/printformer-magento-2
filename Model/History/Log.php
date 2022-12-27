<?php

namespace Rissc\Printformer\Model\History;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Log
 *
 * @package Rissc\Printformer\Model\History
 *
 * @method getDraftId()
 * @method getCreatedAt()
 * @method getStatus()
 * @method getDirection()
 * @method getRequestData()
 * @method getResponseData()
 * @method getApiUrl()
 */
class Log
    extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Rissc\Printformer\Model\ResourceModel\Log');
    }
}
