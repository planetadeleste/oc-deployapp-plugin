<?php

namespace PlanetaDelEste\DeployApp\Components;

use Cms\Classes\ComponentBase;
use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
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
            'description' => 'No description provided yet...',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'frontapp'      => [
                'title' => 'planetadeleste.deployapp::lang.component.deploy.frontapp_title',
                'type'  => 'dropdown',
            ],
            'frontapp_name' => [
                'title' => 'lovata.toolbox::lang.field.name',
                'type'  => 'text',
            ],
            'fromhtml'      => [
                'title' => 'planetadeleste.deployapp::lang.component.deploy.fromhtm_title',
                'type'  => 'checkbox',
            ],
            'resources'     => [
                'title' => 'planetadeleste.deployapp::lang.component.deploy.resources_title',
                'type'  => 'checkbox',
            ],
        ];
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function onRender(): void
    {
        if ((!$iFrontAppId = $this->property('frontapp')) && ($sFrontName = $this->property('frontapp_name'))) {
            $iFrontAppId = FrontApp::where('name', $sFrontName)->value('id');
        }

        if (!$iFrontAppId) {
            $sMessage = isset($sFrontName) ? sprintf('No aplication found with name %s', $sFrontName) : 'No application found.';

            throw new \Exception($sMessage);
        }

        $this->page['token'] = session()->token();
        $obFrontApp          = FrontApp::find($iFrontAppId);
        $this->sBasePath     = str_slug($obFrontApp->name);
        $this->version       = Version::getLatestVersion($iFrontAppId);
        $this->sVersionsPath = $obFrontApp->path;
        $this->buildAssets();
    }

    /**
     * @return void
     *
     * @throws InvalidSelectorException
     */
    protected function buildAssets(): void
    {
        if (!$this->version) {
            return;
        }

        // Construct assets path
        if ($this->isResources()) {
            $sPath = resource_path('views/'.$this->sBasePath.'/'.$this->version);
        } else {
            $arPath   = [
                rtrim($this->sVersionsPath, '/'),
                'assets',
                $this->sBasePath,
            ];
            $arPath[] = $this->version;
            $sPath    = plugins_path(implode('/', $arPath));
        }

        if (!File::exists($sPath)) {
            return;
        }

        $sAssetsPath     = $this->path() ? config('cms.pluginsPath', '/plugins').'/'.$this->path() : '/resources/views/'.$this->sBasePath;
        $sAssetsPath     = rtrim($sAssetsPath, '/');
        $this->assetPath = $sAssetsPath;

        if ($this->isFromHtml()) {
            $this->buildAssetsFromHtml($sPath);
        } else {
            $this->buildAssetsFromFiles($sPath);
        }
    }

    /**
     * @param string $sPath
     *
     * @return void
     */
    protected function buildAssetsFromFiles(string $sPath): void
    {
        $pluginPath = plugins_path($this->property('path'));

        // Find JS files
        $arJSFiles = [
            'chunk'   => [],
            'vendors' => null,
            'app'     => null,
        ];
        collect(File::glob($sPath.'/js/*.js'))
            ->each(
                static function ($sFile) use (&$arJSFiles, $pluginPath): void {
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
                static function ($sFile) use (&$arCSSFiles, $pluginPath): void {
                    $sFile = str_replace($pluginPath.'/', '', $sFile);

                    if (str_contains($sFile, 'app.')) {
                        $arCSSFiles['app'] = $sFile;
                    } elseif (str_contains($sFile, 'vendors.')) {
                        $arCSSFiles['vendors'] = $sFile;
                    }
                }
            );

        $this->fireSystemEvent(self::BEFORE_DEPLOY);

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

    /**
     * @param string $sPath
     *
     * @return void
     *
     * @throws InvalidSelectorException
     */
    protected function buildAssetsFromHtml(string $sPath): void
    {
        $sSearchPath = $this->isResources() ? resource_path('views/'.$this->sBasePath) : plugins_path($this->path());
        $sFilePath   = str_replace($sSearchPath.'/', '', $sPath);
        $sStartsWith = $this->isResources() ? '/resources' : '/plugins';

        $obDoc = new Document();
        $obDoc->loadHtmlFile($sPath.'/index.html');

        // Parse <script />
        $arScript = $obDoc->find('head > script');

        if (!empty($arScript) && is_array($arScript)) {
            foreach ($arScript as $obScript) {
                $arScriptAttr = $obScript->attributes();
                $sSrc         = array_get($arScriptAttr, 'src');
                $sSrc         = $this->getResourcesPath($sSrc);
                array_forget($arScriptAttr, 'src');

// if (($sSrc = array_get($arScriptAttr, 'src')) && !str_contains($sSrc, $sStartsWith)) {
// $sSrc = ltrim($sSrc, './');
// $sSrc = $sFilePath.'/'.$sSrc;
// array_forget($arScriptAttr, 'src');
// }

                $this->addJs($sSrc, $arScriptAttr);
            }
        }

        // Parse <link />
        $arLink = $obDoc->find('head > link');

        if (empty($arLink) || !is_array($arLink)) {
            return;
        }

        foreach ($arLink as $obLink) {
            $arLinkAttr = $obLink->attributes();
            $sRel       = array_get($arLinkAttr, 'rel');
            $sHref      = array_get($arLinkAttr, 'href');
            $sHref      = $this->getResourcesPath($sHref);
            array_forget($arLinkAttr, 'href');

            // Skip rel=icon
            if ($sRel === 'icon') {
                continue;
            }

// if (($sHref = array_get($arLinkAttr, 'href')) && !str_starts_with($sHref, $sStartsWith)) {
// $sHref = ltrim($sHref, './');
// $sHref = $sFilePath.'/'.$sHref;
// array_forget($arLinkAttr, 'href');
// }

            $this->addCss($sHref, $arLinkAttr);
        }
    }

    /**
     * @return array
     */
    public function getFrontappOptions(): array
    {
        return (array) FrontApp::orderBy('name')->lists('name', 'id');
    }

    /**
     * @return array
     */
    public function getPathOptions(): array
    {
        return collect(PluginManager::instance()->getPlugins())
            ->mapWithKeys(
                static function ($obPlugin, $sPlugin) {
                    $sPluginPath       = str_replace('.', '/', strtolower($sPlugin));
                    $sPluginAssetsPath = $sPluginPath.'/assets';

                    return [$sPluginPath => $sPluginAssetsPath];
                }
            )
            ->filter(
                static function ($sPluginPath) {
                    return File::exists(plugins_path($sPluginPath));
                }
            )
            ->toArray();
    }

    /**
     * @param string $sSrc
     *
     * @return string
     */
    protected function getResourcesPath(string $sSrc): string
    {
        $sSrc   = trim($sSrc, '/');
        $arPath = explode('/', $sSrc);

        if ($arPath[0] !== 'resources') {
            array_shift($arPath);
        }

        $sSrc = implode('/', $arPath);

        return str_replace('resources/views/'.$this->sBasePath.'/', '', $sSrc);
    }

    /**
     * @return bool
     */
    protected function isResources(): bool
    {
        return (bool) $this->property('resources', false);
    }

    /**
     * @return bool
     */
    protected function isFromHtml(): bool
    {
        return (bool) $this->property('fromhtml', false);
    }

    /**
     * @return string|null
     */
    protected function path(): ?string
    {
        return $this->property('path');
    }
}
