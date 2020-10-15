<?php namespace PlanetaDelEste\DeployApp\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Class Versions
 * @package PlanetaDelEste\DeployApp\Controllers
 */
class Versions extends Controller
{
    /** @var array */
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
    ];
    /** @var string */
    public $listConfig = 'config_list.yaml';
    /** @var string */
    public $formConfig = 'config_form.yaml';

    /**
     * Versions constructor.
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('PlanetaDelEste.DeployApp', 'deployapp-menu-main', 'deployapp-menu-versions');
    }
}
