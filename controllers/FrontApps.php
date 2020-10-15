<?php namespace PlanetaDelEste\DeployApp\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Class FrontApps
 * @package PlanetaDelEste\DeployApp\Controllers
 */
class FrontApps extends Controller
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
     * FrontApps constructor.
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('PlanetaDelEste.DeployApp', 'deployapp-menu-main', 'deployapp-menu-frontapps');
    }
}
