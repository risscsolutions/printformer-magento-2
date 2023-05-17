<?php
namespace Rissc\Printformer\Model\Api\Webservice\Data;

/**
 * Interface TemplateInterface
 * @package Rissc\Printformer\Model\Api\Webservice\Data
 */
interface TemplateInterface
{

    /**
     * Remove printformer product template by Product ID
     * 
     * @api
     * @param int $productId
     * @return mixed
     */
    public function remove($productId);

    /**
     * Add printformer product template by Product ID
     *
     * @api
     * @param int $productId
     * @return mixed
     */
    public function addTemplate($productId);

}