<?php

namespace Rissc\Printformer\Plugin;

use Magento\Framework\Reflection\FieldNamer;

/**
 * Class FieldNamerPlugin
 *
 * @package Rissc\Printformer\Plugin
 */
class FieldNamerPlugin
{
    static protected $ignoredMethodNames
        = [
            'getEntityType',
            'getEntityIdentifier',
            'getUserIdentifier',
            'getAllowAction',
        ];

    /**
     * @param   FieldNamer  $subject
     * @param   \Closure    $oGetFieldNameForMethodName
     * @param   string      $methodName
     *
     * @return string
     */
    public function aroundGetFieldNameForMethodName(
        FieldNamer $subject,
        \Closure $oGetFieldNameForMethodName,
        string $methodName
    ) {
        if (in_array($methodName, self::$ignoredMethodNames)) {
            return lcfirst(substr($methodName, 3, strlen($methodName) - 1));
        }

        return $oGetFieldNameForMethodName($methodName);
    }
}
