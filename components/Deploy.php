<?php namespace PlanetaDelEste\DeployApp\Components;

use Cms\Classes\ComponentBase;
use DiDom\Document;
use Event;
use File;
use PlanetaDelEste\DeployApp\Models\FrontApp;
use PlanetaDelEste\DeployApp\Models\Version;
use System\Classes\PluginManager;

class Deploy extends ComponentBase
{
    public const BEFORE_DEPLOY = 'planetadeleste.deployapp.before.deploy';

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

    public function componentDetails(): array
    {
        return [
            'name'        => 'Deploy Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties(): array
    {
        return [
            'frontapp'  => [
                'title'    => 'planetadeleste.deployapp::lang.component.deploy.frontapp_title',
                'type'     => 'dropdown',
                'required' => true
            ],
            'fromhtml'  => [
                'title' => 'planetadeleste.deployapp::lang.component.deploy.fromhtm_title',
                'type'  => 'checkbox'
            ],
            'resources' => [
                'title' => 'planetadeleste.deployapp::lang.component.deploy.resources_title',
                'type'  => 'checkbox'
            ],
        ];
    }

    public function onRun(): void
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

    protected function buildAssets(): void
    {
        if (!$this->version) {
            return;
        }

        // Construct assets path
        if ($this->property('resources')) {
            $sPath = resource_path('views/'.$this->sBasePath.'/'.$this->version);
        } else {
            $arPath = [
                rtrim($this->sVersionsPath, '/'),
                'assets',
                $this->sBasePath
            ];
            $arPath[] = $this->version;
            $sPath = plugins_path(implode('/', $arPath));
        }

        if (!File::exists($sPath)) {
            return;
        }

        $sAssetsPath = config('cms.pluginsPath', '/plugins').'/'.$this->property('path');
        $sAssetsPath = rtrim($sAssetsPath, '/');
        $this->assetPath = $sAssetsPath;

        if ($this->property('fromhtml')) {
            $this->buildAssetsFromHtml($sPath);
        } else {
            $this->buildAssetsFromFiles($sPath);
        }
    }

    protected function buildAssetsFromFiles(string $sPath): void
    {
        $pluginPath = plugins_path($this->property('path'));

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
    }

    protected function buildAssetsFromHtml(string $sPath): void
    {
        $pluginPath = plugins_path($this->property('path'));
        $sFilePath = str_replace($pluginPath.'/', '', $sPath);
        $sStartsWith = $this->property('resources') ? '/resources' : '/plugins';

        $obDoc = new Document();
        $obDoc->loadHtmlFile($sPath.'/index.html');

        // Parse <script />
        $arScript = $obDoc->find('head > script');
        if (!empty($arScript) && is_array($arScript)) {
            foreach ($arScript as $obScript) {
                $arScriptAttr = $obScript->attributes();
                trace_log($arScriptAttr);

                if (($sSrc = array_get($arScriptAttr, 'src')) && !str_starts_with($sSrc, $sStartsWith)) {
                    $sSrc = ltrim($sSrc, './');
                    $sSrc = $sFilePath.'/'.$sSrc;
                    array_forget($arScriptAttr, 'src');
                }

                $this->addJs($sSrc, $arScriptAttr);
            }
        }

        // Parse <link />
        $arLink = $obDoc->find('head > link');
        if (!empty($arLink) && is_array($arLink)) {
            foreach ($arLink as $obLink) {
                $arLinkAttr = $obLink->attributes();
                $sRel = array_get($arLinkAttr, 'rel');
                // Skip rel=icon
                if ($sRel === 'icon') {
                    continue;
                }

                if (($sHref = array_get($arLinkAttr, 'href')) && !str_starts_with($sHref, $sStartsWith)) {
                    $sHref = ltrim($sHref, './');
                    $sHref = $sFilePath.'/'.$sHref;
                    array_forget($arLinkAttr, 'href');
                }

                $this->addCss($sHref, $arLinkAttr);
            }
        }
    }

    /**
     * @return array
     */
    public function getFrontappOptions(): array
    {
        return (array)FrontApp::orderBy('name')->lists('name', 'id');
    }

    public function getPathOptions(): array
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
