<?php
namespace VRPayment\Payment\Setup\Patch\Data;
use \Magento\Framework\Setup\Patch\DataPatchInterface;
use \Magento\Framework\Setup\Patch\PatchVersionInterface;
use \Magento\Framework\Module\Setup\Migration;
use \Magento\Framework\Setup\ModuleDataSetupInterface;


/**
 * Class AddSetupData
 * @package VRPayment\Payment\Setup\Patch\Data
 */

class AddSetupDataStateProcessing implements DataPatchInterface
{
    private $status;
    protected $moduleDataSetup;

    /**
     *
     * @param \VRPayment\Payment\Model\Author $status
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(\Magento\Sales\Model\Order\Status $status, ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->status = $status;
        $this->moduleDataSetup = $moduleDataSetup;
    }


    /**
     *  It will create and remove some status/states. 
     *  We've rolled back the first patch, which means we're using the processing status again.
     *  Also I'm making sure we're setting processing, shipped_vrpayment and  pending_payment as default.
     * @inheritDoc
     */
    public function apply()
    {
        $statuses = array(
            array('status' => 'pending', 'label' => 'Hold Delivery')
        );

        foreach ($statuses as $statusData) {
            $this->status->addData($statusData);
            $this->status->getResource()->save($this->status);
            $this->status->assignState('pending', 'pending', true);
        }

        $tableName = $this->moduleDataSetup->getTable('sales_order_status_state');
        $updateSql = "UPDATE " . $tableName . " SET is_default = 1, visible_on_front = 0 WHERE status = 'pending_payment'";
        $this->moduleDataSetup->getConnection()->query($updateSql);
        
        $tableName = $this->moduleDataSetup->getTable('sales_order_status_state');
        $updateSql = "UPDATE " . $tableName . " SET state = 'shipped_vrpayment', is_default = 1 WHERE status = 'shipped_vrpayment'";
        $this->moduleDataSetup->getConnection()->query($updateSql);
        
        $tableName = $this->moduleDataSetup->getTable('sales_order_status_state');
        $updateSql = "UPDATE " . $tableName . " SET state = 'processing', is_default = 1 WHERE status = 'processing'";
        $this->moduleDataSetup->getConnection()->query($updateSql);
        
        $stateToRemove = 'processing_vrpayment';
        $status = $this->status->load($stateToRemove);

        if ($status->getStatus()) {
            $this->status->getResource()->delete($status);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(){
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(){
        return [];
    }
}
