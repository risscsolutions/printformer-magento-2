<?php
namespace Rissc\Printformer\Helper\Sdk;

use Rissc\Printformer\Printformer;
class PrintformerSdkSingleton
{
    private static $instance;

    private $sdk;

    private function __construct(array $config)
    {
        $this->sdk = new Printformer($config);
    }

    public static function getInstance(array $config)
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function getSdk()
    {
        return $this->sdk;
    }
}