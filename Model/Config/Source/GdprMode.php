<?php
/**
 * VR Payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR Payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Config\Source;

/**
 * Provides the integration methods as array options.
 */
class GdprMode implements \Magento\Framework\Option\ArrayInterface
{

    const ENABLED = 'enabled';
    const DISABLED = 'disabled';

    public function toOptionArray()
    {
        return [
            [
                'value' => self::ENABLED,
                'label' => \__('Enabled')
            ],
            [
                'value' => self::DISABLED,
                'label' => \__('Disabled')
            ]
        ];
    }
}