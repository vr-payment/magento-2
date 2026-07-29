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
namespace VRPayment\Payment\Controller\Order;

use VRPayment\Payment\Api\Data\TransactionInfoInterface;
use VRPayment\Payment\Controller\Order\AbstractDownloadDocument;
use VRPayment\PluginCore\Document\RenderedDocument;

/**
 * Frontend controller action to download a packing slip.
 */
class DownloadPackingSlip extends AbstractDownloadDocument
{
    /**
     * @inheritDoc
     */
    protected function isDocumentDownloadAllowed(TransactionInfoInterface $transaction, $storeId): bool
    {
        return $this->documentHelper->isPackingSlipDownloadAllowed($transaction, $storeId);
    }

    /**
     * @inheritDoc
     */
    protected function getDocument(int $spaceId, int $transactionId): RenderedDocument
    {
        return $this->documentService->getPackingSlip($spaceId, $transactionId);
    }
}
