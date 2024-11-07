<?php
/**
 * VR payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Config\Source;

/**
 * Provides the integration methods as array options.
 */
class IntegrationMethod implements \Magento\Framework\Option\ArrayInterface
{

    const IFRAME = 'iframe';
    const LIGHTBOX = 'lightbox';
	const PAYMENT_PAGE = 'paymentpage';

    public function toOptionArray()
    {
        return [
            [
                'value' => self::IFRAME,
                'label' => \__('Iframe')
            ],
            [
                'value' => self::LIGHTBOX,
                'label' => \__('Lightbox')
			]
        ];
    }
}