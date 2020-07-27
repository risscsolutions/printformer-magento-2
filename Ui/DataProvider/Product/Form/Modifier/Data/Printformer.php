<?php
namespace Rissc\Printformer\Ui\DataProvider\Product\Form\Modifier\Data;

use Magento\Catalog\Model\Locator\LocatorInterface;

/**
 * Class Printformer
 * @package Rissc\Printformer\Ui\DataProvider\Product\Form\Modifier\Data
 */
class Printformer
{
    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * Printformer constructor.
     * @param LocatorInterface $locator
     */
    public function __construct(
        LocatorInterface $locator
    ) {
        $this->locator = $locator;
    }

    /**
     * Get Files will be transferred to printformer for current product
     *
     * @return bool
     */
    public function isFilesCanBeTransferredToPrintformer()
    {
        return (bool) $this->locator->getProduct()->getData('files_transfer_to_printformer');
    }
}
