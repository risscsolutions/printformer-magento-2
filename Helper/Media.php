<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;

class Media extends AbstractHelper
{
    const IMAGE_PATH = 'printformer/{type}/%s_%d.png';

    /** @var Filesystem */
    protected $filesystem;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var Api */
    protected $_apiHelper;

    /** @var Config */
    protected $_config;

    /**
     * Media constructor.
     * @param Context $context
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        Api $apiHelper,
        Config $config
    ) {
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->_apiHelper = $apiHelper;
        $this->_config = $config;

        parent::__construct($context);
    }

    /**
     * @param string $draftId
     * @param int $page
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getImageFilePath($draftId, $page = 1, $isThumbnail = false)
    {
        $imagePath = $this->getImagePath($isThumbnail, $isThumbnail = false);

        $mediaDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $mediaDir->create($imagePath);
        return $mediaDir->getAbsolutePath(sprintf($imagePath, $draftId, $page));
    }

    /**
     * @param string $draftId
     * @param int $page
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteImage($draftId, $page = 1, $isThumbnail = false)
    {
        $imagePath = $this->getImagePath($isThumbnail);

        $mediaDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $draftImagePath = sprintf($imagePath, $draftId, $page);
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
    public function deleteAllImages($draftId, $isThumbnail = false)
    {
        $run = true;
        $page = 1;
        while($run) {
            $run = $this->deleteImage($draftId, $page, $isThumbnail);
            $page++;
        }
    }

    /**
     * @param string $draftId
     * @param int $page
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageUrl($draftId, $page = 1, $isThumbnail = false)
    {
        $imagePath = $this->getImagePath($isThumbnail);

        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . sprintf($imagePath, $draftId, $page);
    }

    public function getImagePath($isThumbnail = false)
    {
        return str_replace('{type}', ($isThumbnail ? 'thumbs' : 'preview'), self::IMAGE_PATH);
    }

    /**
     * @param string $draftId
     * @param int $page
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function createThumbnail(string $draftId, $page = 1)
    {
        $jpgImg = $this->_apiHelper->getThumbnail(
            $draftId,
            $this->_apiHelper->getUserIdentifier(),
            $this->_config->getImageThumbnailWidth(),
            $this->_config->getImageThumbnailHeight(),
            $page
        );

        $printformerImage = $jpgImg['content'];

        $imageFilePath = $this->getImageFilePath($draftId, $page, true);

        $image = imagecreatefromstring($printformerImage);

        $width = imagesx($image);
        $height = imagesy($image);

        $out = imagecreatetruecolor($width, $height);
        imagealphablending($out,false);
        $transparentindex = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefill($out, 0, 0, $transparentindex);
        imagesavealpha($out, true);

        imagecopyresampled($out, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        imagepng($out, $imageFilePath, 7);

        imagedestroy($image);
    }

    /**
     * @param string $draftId
     * @param int $page
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function createPreview(string $draftId, $page = 1)
    {
        $jpgImg = $this->_apiHelper->getThumbnail(
            $draftId,
            $this->_apiHelper->getUserIdentifier(),
            $this->_config->getImagePreviewWidth(),
            $this->_config->getImagePreviewHeight(),
            $page
        );

        $printformerImage = $jpgImg['content'];

        $imageFilePath = $this->getImageFilePath($draftId, $page);

        $image = imagecreatefromstring($printformerImage);

        $width = imagesx($image);
        $height = imagesy($image);

        $out = imagecreatetruecolor($width, $height);
        imagealphablending($out,false);
        $transparentindex = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefill($out, 0, 0, $transparentindex);
        imagesavealpha($out, true);

        imagecopyresampled($out, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        imagepng($out, $imageFilePath, 7);

        imagedestroy($image);
    }
}