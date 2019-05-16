<?php
namespace Rissc\Printformer\Gateway;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Exception extends LocalizedException
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception $cause
     */
    public function __construct(Phrase $phrase, \Exception $cause = null)
    {
        parent::__construct($phrase, $cause);
    }
}
