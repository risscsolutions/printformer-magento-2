<?php
namespace Rissc\Printformer\Model\Config\Source;

class Redirect extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const CONFIG_REDIRECT_URL_PRODUCT = '1';
    const CONFIG_REDIRECT_URL_CART    = '2';
    const CONFIG_REDIRECT_URL_ALT     = '3';

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options[] = array(
                'label' => __('Product Page'),
                'value' => self::CONFIG_REDIRECT_URL_PRODUCT,
            );
            $this->_options[] = array(
                'label' => __('Cart Page'),
                'value' => self::CONFIG_REDIRECT_URL_CART,
            );
            $this->_options[] = array(
                'label' => __('Alternative URL'),
                'value' => self::CONFIG_REDIRECT_URL_ALT,
            );
        }
        return $this->_options;
    }
}
