<?php

namespace Rissc\Printformer\Helper\Editor;

use Magento\Framework\App\Helper\AbstractHelper;

class Preselect extends AbstractHelper
{
    public function getPreselectArray($formData)
    {
        $preselectedOptions = [
            'product' => $formData['product'],
            'options' => !empty($formData['options'])
                ? $this->_getOptionsArray($formData['options']) : [],
            'qty'     => ['value' => $formData['qty']],
        ];

        $preselectedOptions['super_attribute']
            = !empty($formData['super_attribute']) ? $this->realignOptions
        ($formData['super_attribute']) : [];

        return $preselectedOptions;
    }

    protected function _getOptionsArray($options)
    {
        $allignedOptions = [];
        foreach ($options as $optionId => $optionValue) {
            $allignedOptions[$optionId] = ['value' => $optionValue];
        }

        return $allignedOptions;
    }

    /**
     * @param   array  $options
     *
     * @return array
     */
    protected function realignOptions(array $options)
    {
        $allignedOptions = [];
        foreach ($options as $optionId => $optionValue) {
            $allignedOptions[$optionId] = ['value' => $optionValue];
        }

        return $allignedOptions;
    }
}
