<?php return [
    'plugin'     => [
        'name'        => 'DeployApp',
        'description' => '',
    ],
    'field'      => [
        'path' => 'Path to deploy versions'
    ],
    'menu'       => [
        'main'      => 'DeployApp',
        'frontapps' => 'FrontApp list',
        'versions'  => 'Version list',
    ],
    'tab'        => [
        'permissions' => 'DeployApp',
    ],
    'comment'    => [],
    'message'    => [],
    'button'     => [],
    'component'  => [
        'deploy' => [
            'path_title'     => 'Versioning path, relative to plugins folder.',
            'frontapp_title' => 'Category where store versioning application'
        ]
    ],
    'permission' => [
        'frontapp' => 'Manage frontapp',
        'version'  => 'Manage version',
    ],
    'frontapp'   => [
        'name'       => 'frontapp',
        'list_title' => 'FrontApp list',
    ],
    'version'    => [
        'name'       => 'version',
        'list_title' => 'Version list',
    ],
];
