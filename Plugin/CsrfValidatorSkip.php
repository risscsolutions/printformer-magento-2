<?php

namespace Rissc\Printformer\Plugin;

use Closure;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CsrfValidatorSkip
 *
 * @package Rissc\Printformer\Plugin
 */
class CsrfValidatorSkip
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CsrfValidatorSkip constructor.
     *
     * @param   LoggerInterface  $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @param   CsrfValidator     $subject
     * @param   Closure           $proceed
     * @param   RequestInterface  $request
     * @param   ActionInterface   $action
     */
    public function aroundValidate(
        $subject,
        Closure $proceed,
        $request,
        $action
    ) {
        if (strpos($request->getPathInfo(), 'printformer/process/draft')
            != false
        ) {
            $this->logger->debug('Callback post-request for draft processing registered: '
                .$request->getUriString());

            return; // Skip CSRF check
        }
        if (strpos($request->getPathInfo(), 'printformer/upload') != false) {
            return; // Skip CSRF check
        }
        $proceed($request, $action); // Proceed Magento 2 core functionalities
    }
}
