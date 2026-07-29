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

use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\ForwardFactory;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Controller\DocumentDownloadResponseTrait;
use VRPayment\PluginCore\Document\DocumentService;
use VRPayment\PluginCore\Document\RenderedDocument;
use VRPayment\PluginCore\Log\LoggerInterface;

/**
 * Base backend controller that resolves the document for download.
 */
abstract class AbstractDownloadDocument extends \VRPayment\Payment\Controller\Adminhtml\Order
{
    use DocumentDownloadResponseTrait;

    /**
     *
     * @var ForwardFactory
     */
    private $resultForwardFactory;

    /**
     *
     * @var FileFactory
     */
    private $fileFactory;

    /**
     *
     * @var TransactionInfoRepositoryInterface
     */
    private $transactionInfoRepository;

    /**
     *
     * @var DocumentService
     */
    protected $documentService;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param FileFactory $fileFactory
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param DocumentService $documentService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        FileFactory $fileFactory,
        TransactionInfoRepositoryInterface $transactionInfoRepository,
        DocumentService $documentService,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->fileFactory = $fileFactory;
        $this->transactionInfoRepository = $transactionInfoRepository;
        $this->documentService = $documentService;
        $this->logger = $logger;
    }

    /**
     * Download the transaction document if allowed.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $transaction = $this->transactionInfoRepository->getByOrderId($orderId);

        try {
            $document = $this->getDocument(
                (int) $transaction->getSpaceId(),
                (int) $transaction->getTransactionId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Document download failed.', [
                'orderId' => $orderId,
                'exception' => $e,
            ]);
            throw $e;
        }

        $this->logger->info('Document downloaded.', [
            'orderId' => $orderId,
            'document' => $document->title,
        ]);

        return $this->createDocumentDownloadResponse($document);
    }

    /**
     * Fetches the specific document for the given transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return RenderedDocument
     */
    abstract protected function getDocument(int $spaceId, int $transactionId): RenderedDocument;
}
