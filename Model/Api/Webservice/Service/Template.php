<?php

namespace Rissc\Printformer\Model\Api\Webservice\Service;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Rissc\Printformer\Model\Api\Webservice\AbstractService;
use Rissc\Printformer\Model\Api\Webservice\Data\TemplateInterface;
use Rissc\Printformer\Model\Catalog\Printformer\ProductFactory as TemplateFactory;
use Rissc\Printformer\Model\ProductFactory;

/**
 * Class Template
 * @package Rissc\Printformer\Model\Api\Webservice\Service
 */
class Template extends AbstractService
    implements TemplateInterface
{

    /**
     * @var TemplateFactory
     */
    protected $templateFactory;

    /**
     * @var ProductFactory
     */
    protected $printformerProductFactory;

    /**
     * Template constructor.
     * @param Request $_request
     * @param TemplateFactory $templateFactory
     * @param ProductFactory $printformerProductFactory
     */
    public function __construct(
        Request $_request,
        TemplateFactory $templateFactory,
        ProductFactory $printformerProductFactory
    ) {
        $this->templateFactory = $templateFactory;
        $this->printformerProductFactory = $printformerProductFactory;

        parent::__construct($_request);
    }


    /**
     * Remove printformer product template by Product ID
     *
     * @param int $productId
     * @return \Magento\Framework\Phrase|mixed|string
     */
    public function remove($productId)
    {
        try {
            $message = '';
            $postParams = $this->getRequest()->getBodyParams();
            if (empty($postParams)) {
                $message = __("Not a valid data.");
            }

            if ($postParams['tplAssignment']) {
                foreach ($postParams['tplAssignment'] as $param) {
                    $template = $this->templateFactory->create();
                    $collection = $template->getCollection()
                        ->addFieldToFilter('product_id', $productId)
                        ->addFieldToFilter('master_id', $param['masterId'])
                        ->addFieldToFilter('intent', $param['intent'])
                        ->addFieldToFilter('store_id', $param['storeId']);

                    if ($collection->getSize()) {
                        foreach ($collection as $item) {
                            $item->delete();
                        }
                        $message .= __('Template with masterId %1 and intent %2 was successful removed. ',
                            $param['masterId'], $param['intent']);
                    } else {
                        $message .= __('Template with masterId %1 and intent %2 not found. ', $param['masterId'],
                            $param['intent']);
                    }
                }
            }
        } catch (\Exception $e) {
            $message = __("Templates cannot be removed.");
        }
        return $message;
    }

    /**
     * Add printformer product template by Product ID
     *
     * @param int $productId
     * @return \Magento\Framework\Phrase|mixed|string
     */
    public function addTemplate($productId)
    {
        try {
            $message = '';
            $postParams = $this->getRequest()->getBodyParams();
            if (empty($postParams)) {
                $message = __("Not a valid data.");
            }

            if ($postParams['tplAssignment']) {
                $masterColumn = array_column($postParams['tplAssignment'], 'masterId');
                $intentColumn = array_column($postParams['tplAssignment'], 'intent');
                $storeColumn = array_column($postParams['tplAssignment'], 'storeId');
                if ($masterColumn != array_unique($masterColumn) && $intentColumn != array_unique($intentColumn)
                    && $storeColumn != array_unique($storeColumn)) {
                    $message = __("A combination of intent, master_id and store_id  must be unique.");
                    return $message;
                }

                $data = [];
                foreach ($postParams['tplAssignment'] as $param) {
                    $template = $this->templateFactory->create();
                    $templateList = $this->printformerProductFactory->create();
                    $collection = $templateList->getCollection()
                        ->addFieldToFilter('master_id', $param['masterId'])
                        ->addFieldToFilter('intent', $param['intent'])
                        ->addFieldToFilter('store_id', $param['storeId']);

                    if ($collection->getSize()) {
                        foreach ($collection as $item) {
                            $item = [
                                'product_id' => $productId,
                                'printformer_product_id' => 15905,
                                'master_id' => $param['masterId'],
                                'store_id' => $param['storeId'],
                                'intent' => $param['intent']
                            ];
                            $checkTemplate = $template->getCollection()
                                ->addFieldToFilter('product_id', $productId)
                                ->addFieldToFilter('printformer_product_id', 15905)
                                ->addFieldToFilter('master_id', $param['masterId'])
                                ->addFieldToFilter('intent', $param['intent'])
                                ->addFieldToFilter('store_id', $param['storeId'])
                                ->setPageSize(1);

                            if ($checkTemplate->getSize()) {
                                foreach ($checkTemplate as $k) {
                                    $item['id'] = $k->getId();
                                }
                            }

                            $template->setData($item);
                            $template->save();
                        }
                        $message .= __('Template with masterId %1 and intent %2 assinment was successful. ',
                            $param['masterId'], $param['intent']);
                    } else {
                        $message .= __('Template with masterId %1 and intent %2 not found. ', $param['masterId'],
                            $param['intent']);
                    }
                }
            }
        } catch (\Exception $e) {
            $message = __("Templates cannot be assigned.");
        }
        return $message;
    }
}