<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Reference;

use function is_string;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    /** @param array<string, string|array{class: string, arguments?: array<string>, factory?: callable}> $servicesFile */
    $loadServices = static function (array $servicesFile, ServicesConfigurator $services): void {
        foreach ($servicesFile as $serviceName => $service) {
            if (is_string($service)) {
                $services->alias($serviceName, $service);
                continue;
            }

            $theService = $services->set($serviceName, $service['class']);
            if (isset($service['factory'])) {
                $theService->factory($service['factory']);
            }

            if (! isset($service['arguments'])) {
                continue;
            }

            foreach ($service['arguments'] as &$argumentName) {
                $argumentName = new Reference($argumentName);
            }

            $theService->args($service['arguments']);
        }
    };

    $servicesFile = include ROOT_PATH . 'app/services.php';
    $loadServices($servicesFile, $services);
    $servicesFile = include ROOT_PATH . 'app/services_controllers.php';
    $loadServices($servicesFile, $services);
};
