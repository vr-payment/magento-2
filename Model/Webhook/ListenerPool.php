<?php
/**
 * VRPayment Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPayment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Webhook;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManager\TMapFactory;

/**
 * Webhook listener pool.
 */
class ListenerPool implements ListenerPoolInterface
{

    /**
     *
     * @var \Magento\Framework\ObjectManager\TMap|ListenerInterface[]
     */
    private $listeners;

    /**
     *
     * @param TMapFactory $tmapFactory
     * @param array $listeners
     */
    public function __construct(TMapFactory $tmapFactory, array $listeners = [])
    {
        $this->listeners = $tmapFactory->create([
            'array' => $listeners,
            'type' => ListenerInterface::class
        ]);
    }

    /**
     * Retrieves listener.
     *
     * @param string $listenerCode
     * @return ListenerInterface
     * @throws NotFoundException
     */
    public function get($listenerCode)
    {
        if (! isset($this->listeners[$listenerCode])) {
            throw new NotFoundException(\__('Listener %1 does not exist.', $listenerCode));
        }

        return $this->listeners[$listenerCode];
    }
}