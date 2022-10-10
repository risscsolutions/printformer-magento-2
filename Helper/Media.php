<?php

namespace Rissc\Printformer\Helper;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Api\Url;

/**
 * Class Media
 * @package Rissc\Printformer\Helper
 */
class Media extends AbstractHelper
{
    const IMAGE_PATH = 'printformer/{type}/%s_%d.png';
    const IMAGE_PARENT_PATH = 'printformer/{type}';

    /** @var Filesystem */
    protected $filesystem;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var Api */
    protected $_apiHelper;

    /** @var Url */
    protected $_urlHelper;

    /** @var Config */
    protected $_config;

    /**
     * Media constructor.
     * @param Context $context
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param Api $apiHelper
     * @param Config $config
     * @param Url $urlHelper
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        Api $apiHelper,
        Config $config,
        Url $urlHelper
    )
    {
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->_apiHelper = $apiHelper;
        $this->_config = $config;
        $this->_urlHelper = $urlHelper;

        parent::__construct($context);
    }

    /**
     * @param string $draftId
     * @param int $page
     * @param bool $isThumbnail
     *
     * @return string
     *
     * @throws FileSystemException
     */
    public function getImageFilePath(
        $draftId,
        $page = 1,
        $isThumbnail = false
    )
    {
        $imagePathDefaultString = $this->getImagePath($isThumbnail);
        $imagePath = sprintf($imagePathDefaultString, $draftId, $page);

        $imageParentFolderPath = $this->getImageParentFolderPath($isThumbnail);
        $mediaDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $mediaDir->create($imageParentFolderPath);
        return $mediaDir->getAbsolutePath(sprintf($imagePath, $draftId, $page));
    }

    /**
     * @param string $draftId
     * @param int $page
     *
     * @return bool
     *
     * @throws FileSystemException
     */
    public function deleteImage(
        $draftId,
        $page = 1,
        $isThumbnail = false
    )
    {
        $imagePath = $this->getImagePath($isThumbnail);

        $mediaDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $draftImagePath = sprintf($imagePath, $draftId, $page);
        if ($mediaDir->isExist($draftImagePath)) {
            $mediaDir->delete($draftImagePath);
            return true;
        }

        return false;
    }

    /**
     * Delete all draft images
     *
     * @param string $draftId
     *
     * @throws FileSystemException
     */
    public function deleteAllImages(
        $draftId,
        $isThumbnail = false
    )
    {
        $run = true;
        $page = 1;
        while ($run) {
            $run = $this->deleteImage($draftId, $page, $isThumbnail);
            $page++;
        }
    }

    /**
     * @param $draftHash
     * @param int $uniqueGetParam
     * @return string
     */
    public function getThumbnail(
        $draftHash,
        $uniqueGetParam = 0
    )
    {
        $thumbnailUrl = $this->getThumbnail($draftHash);
        if ($uniqueGetParam) {
            $thumbnailUrl = $this->_urlHelper->appendUniqueGetParam($thumbnailUrl);
        }
        return $thumbnailUrl;
    }

    /**
     * @param $draftId
     * @param int $page
     * @param bool $isThumbnail
     * @param int $uniqueGetParam
     * @return string
     * @throws NoSuchEntityException
     */
    public function getImageUrl(
        $draftId,
        $page = 1,
        $isThumbnail = false,
        $uniqueGetParam = 1
    )
    {
        $imagePath = $this->getImagePath($isThumbnail);

        $thumbnailUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . sprintf($imagePath, $draftId, $page);

        if ($uniqueGetParam) {
            $thumbnailUrl = $this->_urlHelper->appendUniqueGetParam($thumbnailUrl);
        }

        return $thumbnailUrl;
    }

    /**
     * @param bool $isThumbnail
     *
     * @return string
     */
    public function getImagePath($isThumbnail = false)
    {
        return str_replace('{type}', ($isThumbnail ? 'thumbs' : 'preview'), self::IMAGE_PATH);
    }

    /**
     * @param bool $isThumbnail
     *
     * @return string
     */
    public function getImageParentFolderPath($isThumbnail = false)
    {
        return str_replace('{type}', ($isThumbnail ? 'thumbs' : 'preview'), self::IMAGE_PARENT_PATH);
    }

    /**
     * @param string $draftId
     * @param int $page
     *
     * @throws FileSystemException
     * @throws AlreadyExistsException
     */
    public function createThumbnail(
        string $draftId,
        $page = 1
    )
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
        imagealphablending($out, false);
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
     * @throws FileSystemException
     * @throws AlreadyExistsException
     */
    public function createPreview(
        string $draftId,
        $page = 1
    )
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
        imagealphablending($out, false);
        $transparentindex = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefill($out, 0, 0, $transparentindex);
        imagesavealpha($out, true);

        imagecopyresampled($out, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        imagepng($out, $imageFilePath, 7);

        imagedestroy($image);
    }

    /**
     * Add draft-image-item to image-collection
     *
     * @param $draftIds
     * @param ProductModel $product
     * @param Collection $result
     * @return Collection
     */
    public function loadDraftImagesToMainImage(
        $draftIds,
        ProductModel $product,
        Collection $result
    )
    {
        if ($this->_config->isUseImagePreview()) {
            if (!empty($draftIds)) {
                //load all images from product
                $items = $product->getMediaGalleryImages()->getItems();

                //remove all already loaded pf images because on multiple getGalleryImages-call the image from cache
                //does not work for printformer images
                foreach ($items as $itemKey => $item) {
                    if ($item['label'] === 'Image Printformer') {
                        $result->removeItemByKey($itemKey);
                    }
                }

                //on multiple calls always set counter to last origin product image + 1
                if (!empty($items)) {
                    $lastItem = array_pop($items);
                    if ($lastItem) {
                        if ($lastItemId = $lastItem->getId()) {
                            $counter = $lastItemId + 1;
                        }
                    }
                }

                foreach ($draftIds as $draftIdKey => $draftId) {
                    $printformerDraft = $this->_apiHelper->getDraftUsagePageInfo($draftId, $this->_apiHelper::DRAFT_USAGE_PAGE_INFO_PREVIEW);
                    $pages = isset($printformerDraft[$draftId]['pages']) ? $printformerDraft[$draftId]['pages'] : 1;

                    for ($index = 0; $index < $pages; $index++) {
                        try {
                            $imagePreviewFilePath = $this->getImageFilePath($draftId, ($index + 1));
                            $additionalHash = '';
                            if (file_exists($imagePreviewFilePath)) {
                                $additionalHash = '?hash=' . filemtime($imagePreviewFilePath);
                            }
                            $imagePreviewUrl = $this->getImagePreviewUrl(($index + 1), $draftId);
                            $fullImagePreviewUrl = $imagePreviewUrl . $additionalHash;
                            $result->addItem(new DataObject([
                                'id' => $index + $counter,
                                'small_image_url' => $fullImagePreviewUrl,
                                'medium_image_url' => $fullImagePreviewUrl,
                                'large_image_url' => $fullImagePreviewUrl,
                                'is_main_image' => ($index + $counter == 0),
                                'file' => 'Image Printformer',
                                'path' => $imagePreviewFilePath,
                                'position' => 1,
                                'label' => 'Image Printformer',
                                'disabled' => 0,
                                'media_type' => 'image'
                            ]));
                        } catch (\Exception $e) {
                            $this->_logger->error($e->getMessage());
                            $this->_logger->error($e->getTraceAsString());
                        }
                    }

                    $counter += 100;
                }
            }
        }

        return $result;
    }

    /**
     * Load draft image pages to result collection
     *
     * @param $draftIds
     * @param $result
     * @return mixed
     */
    public function loadDraftImagesToAdditionalImages(
        $draftIds,
        $result
    )
    {
        if ($this->_config->isUseImagePreview()) {
            if (!empty($draftIds)) {
                $counter = 0;
                $result->removeAllItems();

                foreach ($draftIds as $draftIdKey => $draftId) {
                    $printformerDraft = $this->_apiHelper->getDraftUsagePageInfo($draftId, $this->_apiHelper::DRAFT_USAGE_PAGE_INFO_PREVIEW);
                    $pages = isset($printformerDraft[$draftId]['pages']) ? $printformerDraft[$draftId]['pages'] : 1;

                    for ($index = 0; $index < $pages; $index++) {
                        try {
                            $imagePreviewFilePath = $this->getImageFilePath($draftId, ($index + 1));
                            $additionalHash = '';
                            if (file_exists($imagePreviewFilePath)) {
                                $additionalHash = '?hash=' . filemtime($imagePreviewFilePath);
                            }
                            $imagePreviewUrl = $this->getImagePreviewUrl(($index + 1), $draftId);
                            $fullImagePreviewUrl = $imagePreviewUrl . $additionalHash;
                            $result->addItem(new DataObject(
                                                 [
                                                     'thumb' => $fullImagePreviewUrl,
                                                     'img' => $fullImagePreviewUrl,
                                                     'full' => $fullImagePreviewUrl,
                                                     'caption' => "Image Printformer",
                                                     'position' => "1",
                                                     'isMain' => ($index + $counter == 0),
                                                     'type' => 'image',
                                                     'videoUrl' => null
                                                 ]));
                            $counter += 100;
                        } catch (\Exception $e) {
                            $this->_logger->error($e->getMessage());
                            $this->_logger->error($e->getTraceAsString());
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Create / load file by draft-id and return url
     *
     * @param int $page
     * @param string $draftId
     * @return null|string
     */
    private function getImagePreviewUrl(
        int $page,
        $draftId
    )
    {
        $url = null;
        if ($this->_config->isUseImagePreview() && !empty($draftId)) {
            try {
                $filePath = $this->getImageFilePath($draftId, $page, false);
                if (!file_exists($filePath) || !is_array(getimagesize($filePath))) {
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
                    imagealphablending($out, false);
                    $transparentindex = imagecolorallocatealpha($out, 0, 0, 0, 127);
                    imagefill($out, 0, 0, $transparentindex);
                    imagesavealpha($out, true);
                    imagecopyresized($out, $image, 0, 0, 0, 0, $width, $height, $width, $height);
                    imagepng($out, $imageFilePath);
                    $this->_eventManager->dispatch('printformer_image_preview_create', [
                        'printformer_image' => $printformerImage,
                        'original_image' => $image,
                        'width' => $width,
                        'height' => $height,
                        'final_image' => $out,
                        'image_path' => $imageFilePath
                    ]);
                    imagedestroy($image);
                    $this->draftImageCreated[$draftId . $page] = true;
                }

                $url = $this->getImageUrl($draftId, $page, false, 0);
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
                $this->_logger->error($e->getTraceAsString());
            }
        }

        return $url;
    }

    /**
     * Get image url by draft id (first if comma separated string) if file exists
     *
     * @param string $draftIds
     * @return string
     */
    public function loadThumbsImageUrlByDraftId(string $draftIds)
    {
        $result = false;
        if ($this->_config->isUseImagePreview()) {
            if (!empty($draftIds)){
                $draftIds = explode(',', $draftIds)[0];
                try {
                    if (!file_exists($this->getImageFilePath($draftIds, 1, true))) {
                        $this->createThumbnail($draftIds);
                    }
                    $result = $this->getImageUrl($draftIds, 1, true);
                } catch (AlreadyExistsException|FileSystemException|NoSuchEntityException $e) {
                }
            }
        }

        return $result;
    }
}