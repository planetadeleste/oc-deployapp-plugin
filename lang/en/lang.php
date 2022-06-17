<?php return [
    'plugin'     => [
        'name'        => 'DeployApp',
        'description' => '',
    ],
    'field'      => [
        'path'         => 'Path to deploy versions',
        'path_comment' => 'Path must have `assets` folder inside. Ex. `{author}/{plugin}`'
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
            'path_title'      => 'Versioning path, relative to plugins folder.',
            'frontapp_title'  => 'Category where store versioning application',
            'fromhtm_title'   => 'Get assets from index.html (vite method)',
            'resources_title' => 'Load index.html from resources folder',
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
