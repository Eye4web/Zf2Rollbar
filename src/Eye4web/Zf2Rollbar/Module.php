<?php

namespace Eye4web\Zf2Rollbar;

use Application\Entity\User;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $application = $e->getApplication();
        $eventManager = $application->getEventManager();
        $serviceManager = $application->getServiceManager();

        $config = $serviceManager->get('Config');
        if (!isset($config['eye4web']['zf2rollbar'])) {
            throw new \Exception('Rollbar configuration missing. Please copy .dist config file into your autoloader directory.');
        }
        $rollbarConfig = $config['eye4web']['zf2rollbar'];

        if ($serviceManager->has('zfcuser_auth_service')) {
            $authService = $serviceManager->get('zfcuser_auth_service');
            if ($authService->hasIdentity()) {
                $user = $authService->getIdentity();
                $rollbarConfig['person'] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getDisplayName()
                ];
            }
        }

        \Rollbar::init($rollbarConfig, $set_exception_handler = false, $set_error_handler = true);

        $eventManager->attach('dispatch.error', function($event) {
            $exception = $event->getResult()->exception;
            if ($exception) {
                \Rollbar::report_exception($exception);
            }
        });
        $eventManager->attach('render.error', function($event) {
            $exception = $event->getResult()->exception;
            if ($exception) {
                \Rollbar::report_exception($exception);
            }
        });
    }
}
