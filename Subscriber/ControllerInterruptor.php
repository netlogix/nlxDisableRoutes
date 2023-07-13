<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxDisableRoutes\Subscriber;

use Enlight\Event\SubscriberInterface;
use Monolog\Logger;

class ControllerInterruptor implements SubscriberInterface
{
    /** @var string[] */
    private $pluginConfig;

    /** @var Logger */
    private $logger;

    /**
     * @param string[] $pluginConfig
     */
    public function __construct(
        array $pluginConfig,
        Logger $logger
    ) {
        $this->pluginConfig = $pluginConfig;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
        ];
    }

    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $eventArgs): void
    {
        /** @var \Enlight_Controller_Action $subject */
        $subject = $eventArgs->getSubject();
        $eventName = $subject->Front()->Dispatcher()->getFullActionName($eventArgs->getRequest());

        if (empty($eventName) || $this->shouldInterrupt($eventName)) {
            return;
        }
        $action = $this->pluginConfig['rejectAction'];

        $this->logger->debug('Interrupted access to ' . $eventName);

        switch ($action) {
            case 'redirectHome':
                $subject->redirect($eventArgs->getRequest()->getBaseUrl());
                break;
            case 'genericError':
                $subject->forward('genericError', 'Error', 'Frontend');
                break;
            case 'throwException':
                // Do throw an exception as default, so "fall through":
            default:
                throw new \RuntimeException();
                break;
        }
    }

    private function shouldInterrupt(string $action): bool
    {
        $actions = \explode(',', $this->pluginConfig['disabledControllerActions']);
        return (bool) (\in_array($action, $actions));
    }
}
