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
namespace VRPayment\Payment\Controller\Adminhtml\Order;

use VRPayment\PluginCore\Document\RenderedDocument;

/**
 * Backend controller action to download a packing slip.
 */
class DownloadPackingSlip extends AbstractDownloadDocument
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::shipment';

    /**
     * @inheritDoc
     */
    protected function getDocument(int $spaceId, int $transactionId): RenderedDocument
    {
        return $this->documentService->getPackingSlip($spaceId, $transactionId);
    }
}
