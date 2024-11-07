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
namespace VRPayment\Payment\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\CreditmemoRepository;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Sdk\Model\EntityQuery;
use VRPayment\Sdk\Service\RefundService;

/**
 * Backend controller action to download a refund document.
 */
class DownloadRefund extends \VRPayment\Payment\Controller\Adminhtml\Order
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::sales_creditmemo';

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
     * @var Helper
     */
    private $helper;

    /**
     *
     * @var TransactionInfoRepositoryInterface
     */
    private $transactionInfoRepository;

    /**
     *
     * @var CreditmemoRepository
     */
    private $creditmemoRepository;

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param FileFactory $fileFactory
     * @param Helper $helper
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param ApiClient $apiClient
     * @param CreditmemoRepository $creditmemoRepository
     */
    public function __construct(Context $context, ForwardFactory $resultForwardFactory, FileFactory $fileFactory,
        Helper $helper, TransactionInfoRepositoryInterface $transactionInfoRepository, ApiClient $apiClient,
        CreditmemoRepository $creditmemoRepository)
    {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->fileFactory = $fileFactory;
        $this->helper = $helper;
        $this->transactionInfoRepository = $transactionInfoRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->apiClient = $apiClient;
    }

    public function execute()
    {
        $creditmemoId = $this->getRequest()->getParam('creditmemo_id');
        if ($creditmemoId) {
            $creditmemo = $this->creditmemoRepository->get($creditmemoId);
            if ($creditmemo->getVrpaymentExternalId() == null) {
                return $this->resultForwardFactory->create()->forward('noroute');
            }

            $transaction = $this->transactionInfoRepository->getByOrderId($creditmemo->getOrderId());
            $refund = $this->getRefundByExternalId($transaction->getSpaceId(),
                $creditmemo->getVrpaymentExternalId());
            $document = $this->apiClient->getService(RefundService::class)->getRefundDocument(
                $transaction->getSpaceId(), $refund->getId());
            return $this->fileFactory->create($document->getTitle() . '.pdf', \base64_decode($document->getData()),
                DirectoryList::VAR_DIR, 'application/pdf');
        } else {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
    }

    /**
     * Fetches the refund's latest state from VR payment by its external ID.
     *
     * @param int $spaceId
     * @param string $externalId
     * @throws \Exception
     * @return \VRPayment\Sdk\Model\Refund
     */
    private function getRefundByExternalId($spaceId, $externalId)
    {
        $query = new EntityQuery();
        $query->setFilter($this->helper->createEntityFilter('externalId', $externalId));
        $query->setNumberOfEntities(1);
        $result = $this->apiClient->getService(RefundService::class)->search($spaceId, $query);
        if (! empty($result)) {
            return \current($result);
        } else {
            throw new LocalizedException(\__('The refund could not be found.'));
        }
    }
}