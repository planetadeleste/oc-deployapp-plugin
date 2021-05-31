<?php namespace PlanetaDelEste\DeployApp\Components;

use Cms\Classes\ComponentBase;
use Event;
use File;
use PlanetaDelEste\DeployApp\Models\FrontApp;
use PlanetaDelEste\DeployApp\Models\Version;
use System\Classes\PluginManager;

class Deploy extends ComponentBase
{
    const BEFORE_DEPLOY = 'planetadeleste.deployapp.before.deploy';
    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $sBasePath;

    /**
     * @var string
     */
    protected $sVersionsPath;

    public function componentDetails()
    {
        return [
            'name'        => 'Deploy Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'frontapp' => [
                'title'    => 'planetadeleste.deployapp::lang.component.deploy.frontapp_title',
                'type'     => 'dropdown',
                'required' => true
            ]
        ];
    }

    public function onRun()
    {
        $iFrontAppId = $this->property('frontapp');
        if (!$iFrontAppId) {
            return;
        }
        $this->page['token'] = session()->token();
        $obFrontApp = FrontApp::find($iFrontAppId);
        $this->sBasePath = str_slug($obFrontApp->name);
        $this->version = Version::getLatestVersion($iFrontAppId);
        $this->sVersionsPath = $obFrontApp->path;
        $this->buildAssets();
    }

    protected function buildAssets()
    {
        if (!$this->version) {
            return;
        }

        // Construct assets path
        $arPath = [
            rtrim($this->sVersionsPath, '/'),
            'assets',
            $this->sBasePath
        ];
        $arPath[] = $this->version;
        $sPath = plugins_path(join('/', $arPath));

        if (!File::exists($sPath)) {
            return;
        }

        $pluginPath = plugins_path($this->property('path'));
        $this->assetPath = config('cms.pluginsPath', '/plugins').'/'.$this->property('path');

        // Find JS files
        $arJSFiles = [
            'chunk'   => [],
            'vendors' => null,
            'app'     => null
        ];
        collect(File::glob($sPath.'/js/*.js'))
            ->each(
                function ($sFile) use (&$arJSFiles, $pluginPath) {
                    $sFile = str_replace($pluginPath.'/', '', $sFile);
                    if (str_contains($sFile, 'app.')) {
                        $arJSFiles['app'] = $sFile;
                    } elseif (str_contains($sFile, 'vendors.')) {
                        $arJSFiles['vendors'] = $sFile;
                    } else {
                        $arJSFiles['chunk'][] = $sFile;
                    }
                }
            );

        // Find CSS files
        $arCSSFiles = ['app' => null, 'vendors' => null];
        collect(File::glob($sPath.'/css/*.css'))
            ->each(
                function ($sFile) use (&$arCSSFiles, $pluginPath) {
                    $sFile = str_replace($pluginPath.'/', '', $sFile);
                    if (str_contains($sFile, 'app.')) {
                        $arCSSFiles['app'] = $sFile;
                    } elseif (str_contains($sFile, 'vendors.')) {
                        $arCSSFiles['vendors'] = $sFile;
                    }
                }
            );

        Event::fire(self::BEFORE_DEPLOY, [$this]);

        // Add chunk js files
        foreach ($arJSFiles['chunk'] as $jsFile) {
            $this->addCss($jsFile, ['rel' => 'prefetch']);
        }

        // Add preload styles
//        $attrs = ['rel' => 'preload', 'as' => 'style'];
//        $this->addCss($arCSSFiles['app'], $attrs);
//        $this->addCss($arCSSFiles['vendors'], $attrs);

        // Add modulepreload js
        $attrs = ['rel' => 'modulepreload', 'as' => 'script'];
        $this->addCss($arJSFiles['app'], $attrs);
        $this->addCss($arJSFiles['vendors'], $attrs);

        // Add stylesheets
        $this->addCss($arCSSFiles['vendors']);
        $this->addCss($arCSSFiles['app']);

        // Add JS
        $this->addJs($arJSFiles['app'], ['type' => 'module']);
        $this->addJs($arJSFiles['vendors'], ['type' => 'module']);


        // Add Legacy JS
//        $this->addJs('assets/js/legacy.js');
//        $this->addJs($assetsPath.'/js/chunk-vendors-legacy.js', ['nomodule' => '']);
//        $this->addJs($assetsPath.'/js/app-legacy.js', ['nomodule' => '']);
    }

    /**
     * @return array
     */
    public function getFrontappOptions(): array
    {
        return (array)FrontApp::orderBy('name')->lists('name', 'id');
    }

    public function getPathOptions()
    {
        return collect(PluginManager::instance()->getPlugins())
            ->mapWithKeys(
                function ($obPlugin, $sPlugin) {
                    $sPluginPath = str_replace('.', '/', strtolower($sPlugin));
                    $sPluginAssetsPath = $sPluginPath.'/assets';
                    return [$sPluginPath => $sPluginAssetsPath];
                }
            )
            ->filter(
                function ($sPluginPath) {
                    return File::exists(plugins_path($sPluginPath));
                }
            )
            ->toArray();
    }
}
