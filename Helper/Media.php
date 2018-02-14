<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;

class Media extends AbstractHelper
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $imagePath = 'printformer/preview/%s_%d.png';

    /**
     * @var string
     */
    protected $imageUrlPath = 'pub/media/printformer/preview/%s_%d.png';

    /**
     * @var string
     */
    protected $imageFolder = 'printformer/preview';

    /**
     * Media constructor.
     * @param Context $context
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param string $draftId
     * @param int $page
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getImageFilePath($draftId, $page = 1)
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $mediaDir->create($this->imageFolder);
        return $mediaDir->getAbsolutePath(sprintf($this->imagePath, $draftId, $page));
    }

    /**
     * @param string $draftId
     * @param int $page
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteImage($draftId, $page = 1)
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $draftImagePath = sprintf($this->imagePath, $draftId, $page);
        if($mediaDir->isExist($draftImagePath)) {
            $mediaDir->delete($draftImagePath);
            return true;
        }

        return false;
    }

    /**
     * Delete all draft images
     * @param string $draftId
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteAllImages($draftId)
    {
        $run = true;
        $page = 1;
        while($run) {
            $run = $this->deleteImage($draftId, $page);
            $page++;
        }
    }

    /**
     * @param string $draftId
     * @param int $page
     * @return string
     */
    public function getImageUrl($draftId, $page = 1)
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) . sprintf($this->imageUrlPath, $draftId, $page);
    }
}