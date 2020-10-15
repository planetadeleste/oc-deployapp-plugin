<?php namespace PlanetaDelEste\DeployApp;

use PlanetaDelEste\DeployApp\Components\Deploy;
use System\Classes\PluginBase;

/**
 * Class Plugin
 * @package PlanetaDelEste\DeployApp
 */
class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            Deploy::class => 'deploy'
        ];
    }
}
