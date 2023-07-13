<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace spec\nlxDisableRoutes\Subscriber;

use Enlight\Event\SubscriberInterface;
use Monolog\Logger;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use nlxDisableRoutes\Subscriber\ControllerInterruptor;

class ControllerInterruptorSpec extends ObjectBehavior
{
    public function let(
        Logger $logger,
        \Enlight_Controller_ActionEventArgs $eventArgs,
        \Enlight_Controller_Action $controller,
        \Enlight_Controller_Front $front,
        \Enlight_Controller_Dispatcher_Default $dispatcher,
        \Enlight_Controller_Request_Request $request
    ) {
        $this->beConstructedWith(
            [
                'disabledControllerActions' => '',
                'rejectAction' => 'redirectHome',
            ],
            $logger
        );

        $eventArgs
            ->getSubject()
            ->willReturn($controller);
        $eventArgs
            ->getRequest()
            ->willReturn($request);

        $controller
            ->Front()
            ->willReturn($front);

        $front
            ->Dispatcher()
            ->willReturn($dispatcher);

        $dispatcher
            ->getFullActionName(Argument::any())
            ->willReturn('Test_Controller_Action');

        $request
            ->getBaseUrl()
            ->willReturn('HOME');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ControllerInterruptor::class);
    }

    public function it_implements_correct_interface()
    {
        $this->shouldImplement(SubscriberInterface::class);
    }

    public function it_can_ignore_allowed(
        \Enlight_Controller_ActionEventArgs $eventArgs,
        Logger $logger
    ) {
        $logger
            ->debug(Argument::any())
            ->shouldNotBeCalled();

        $this->onPreDispatch($eventArgs);
    }

    public function it_can_redirect(
        \Enlight_Controller_ActionEventArgs $eventArgs,
        Logger $logger,
        \Enlight_Controller_Action $controller
    ) {
        $this->beConstructedWith(
            [
                'disabledControllerActions' => 'Test_Controller_Action,Test_Controller_Action2',
                'rejectAction' => 'redirectHome',
            ],
            $logger
        );

        $logger
            ->debug(Argument::containingString('Interrupted access'))
            ->shouldBeCalled();

        $controller
            ->redirect('HOME')
            ->shouldBeCalled();

        $this->onPreDispatch($eventArgs);
    }

    public function it_can_forward_to_generic_error(
        \Enlight_Controller_ActionEventArgs $eventArgs,
        Logger $logger,
        \Enlight_Controller_Action $controller
    ) {
        $this->beConstructedWith(
            [
                'disabledControllerActions' => 'Test_Controller_Action,Test_Controller_Action2',
                'rejectAction' => 'genericError',
            ],
            $logger
        );

        $logger
            ->debug(Argument::containingString('Interrupted access'))
            ->shouldBeCalled();

        $controller
            ->forward(Argument::exact('genericError'), Argument::exact('Error'), Argument::exact('Frontend'))
            ->shouldBeCalled();

        $this->onPreDispatch($eventArgs);
    }

    public function it_can_throw_exception(
        \Enlight_Controller_ActionEventArgs $eventArgs,
        Logger $logger
    ) {
        $this->beConstructedWith(
            [
                'disabledControllerActions' => 'Test_Controller_Action,Test_Controller_Action2',
                'rejectAction' => 'throwException',
            ],
            $logger
        );

        $logger
            ->debug(Argument::containingString('Interrupted access'))
            ->shouldBeCalled();

        $this
            ->shouldThrow(\RuntimeException::class)
            ->during('onPreDispatch', [$eventArgs]);
    }
}
