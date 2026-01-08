<?php

declare(strict_types=1);

use PhpMyAdmin\Container\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    /** @var array<string, array{class: string, arguments?: array<string>, factory?: callable}> $servicesFile */
    $servicesFile = include ROOT_PATH . 'app/services.php';
    ContainerBuilder::loadServices($servicesFile, $services);

    /** @var array<string, array{class: string, arguments?: array<string>, factory?: callable}> $servicesFile */
    $servicesFile = include ROOT_PATH . 'app/services_controllers.php';
    ContainerBuilder::loadServices($servicesFile, $services);
};
