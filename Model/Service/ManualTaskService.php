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
namespace VRPayment\Payment\Model\Service;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as StorageWriter;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Sdk\Model\ManualTaskState;
use VRPayment\Sdk\Service\ManualTaskService as ManualTaskApiService;

/**
 * Service to handle manual tasks.
 */
class ManualTaskService
{

    const CONFIG_KEY = 'vrpayment_payment/general/manual_tasks';

    /**
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    /**
     *
     * @var StorageWriter
     */
    private $configWriter;

    /**
     *
     * @var Helper
     */
    private $helper;

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $configCollectionFactory
     * @param StorageWriter $configWriter
     * @param Helper $helper
     * @param ApiClient $apiClient
     */
    public function __construct(StoreManagerInterface $storeManager, ScopeConfigInterface $scopeConfig,
        CollectionFactory $configCollectionFactory, StorageWriter $configWriter, Helper $helper, ApiClient $apiClient)
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->configWriter = $configWriter;
        $this->helper = $helper;
        $this->apiClient = $apiClient;
    }

    /**
     * Gets the number of open manual tasks by website.
     *
     * @return array
     */
    public function getNumberOfManualTasks()
    {
        $numberOfManualTasks = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $websiteNumberOfManualTasks = $this->configCollectionFactory->create()
                ->addFieldToFilter('scope', ScopeInterface::SCOPE_WEBSITE)
                ->addFieldToFilter('scope_id', $website->getId())
                ->addFieldToFilter('path', self::CONFIG_KEY)
                ->getFirstItem()
                ->getValue();
            if (! empty($websiteNumberOfManualTasks)) {
                $numberOfManualTasks[$website->getId()] = $websiteNumberOfManualTasks;
            }
        }
        return $numberOfManualTasks;
    }

    /**
     * Updates the number of open manual tasks.
     *
     * @return array
     */
    public function update()
    {
        $numberOfManualTasks = [];
        $spaceIds = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $spaceId = $this->scopeConfig->getValue('vrpayment_payment/general/space_id',
                ScopeInterface::SCOPE_WEBSITE, $website->getId());
            if ($spaceId && ! \in_array($spaceId, $spaceIds)) {
                $websiteNumberOfManualTasks = $this->apiClient->getService(ManualTaskApiService::class)->count($spaceId,
                    $this->helper->createEntityFilter('state', ManualTaskState::OPEN));
                $this->configWriter->save(self::CONFIG_KEY, $websiteNumberOfManualTasks, ScopeInterface::SCOPE_WEBSITE,
                    $website->getId());
                if (! empty($websiteNumberOfManualTasks)) {
                    $numberOfManualTasks[$website->getId()] = $websiteNumberOfManualTasks;
                }
                $spaceIds[] = $spaceId;
            } else {
                $this->configWriter->delete(self::CONFIG_KEY, ScopeInterface::SCOPE_WEBSITE, $website->getId());
            }
        }
        return $numberOfManualTasks;
    }
}