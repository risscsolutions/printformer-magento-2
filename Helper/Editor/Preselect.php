<?php

namespace Rissc\Printformer\Helper\Editor;

use Magento\Framework\App\Helper\AbstractHelper;

class Preselect extends AbstractHelper
{
    public function getPreselectArray($formData)
    {
        $preselectedOptions = [
            'product' => $formData['product'],
            'options' => !empty($formData['options']) ? $this->_getOptionsArray($formData['options']) : [],
            'qty' => ['value' => $formData['qty']]
        ];

        return $preselectedOptions;
    }

    protected function _getOptionsArray($options)
    {
        $allignedOptions = [];
        foreach($options as $optionId => $optionValue)
        {
            $allignedOptions[$optionId] = ['value' => $optionValue];
        }

        return $allignedOptions;
    }
}