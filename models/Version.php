<?php

namespace PlanetaDelEste\DeployApp\Models;

use Db;
use DiDom\Document;
use Exception;
use Log;
use Model;
use October\Rain\Database\Relations\BelongsTo;
use PlanetaDelEste\DeployApp\Models\FrontApp;
use Storage;

/**
 * Class Version
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 *
 * @property int $id
 * @property int $frontapp_id
 * @property string                    $version
 * @property string                    $description
 * @property array|null                $assets
 * @property \October\Rain\Argon\Argon $created_at
 * @property \October\Rain\Argon\Argon $updated_at
 *
 * Relations
 * @property FrontApp                  $frontapp
 *
 * @method BelongsTo|FrontApp          frontapp()
 */
class Version extends Model
{
    /**
     * @var string
     */
    public $table = 'planetadeleste_deployapp_versions';

    /**
     * @var array
     */
    protected $jsonable = ['assets'];

    /**
     * @var array
     */
    public $rules = ['version' => 'required'];

    /**
     * @var array
     */
    public $fillable = ['version', 'description', 'frontapp_id', 'assets'];

    /**
     * @var array
     */
    public $cached = [
        'id',
        'version',
        'description',
        'frontapp_id',
        'assets'
    ];

    /**
     * @var array
     */
    public $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'frontapp' => [FrontApp::class]
    ];

    /**
     * @param int $iFrontAppId
     *
     * @return \Illuminate\Database\Eloquent\Model|static|\October\Rain\Database\QueryBuilder|null
     */
    public static function getLatest(int $iFrontAppId)
    {
        return static::where('frontapp_id', $iFrontAppId)->orderByDesc(static::rawColumn())->first();
    }

    /**
     * @param int $iFrontAppId
     *
     * @return string|null
     */
    public static function getLatestVersion(int $iFrontAppId): ?string
    {
        $obModel = static::getLatest($iFrontAppId);

        return $obModel ? $obModel->version : null;
    }

    /**
     * @return mixed
     */
    public static function rawColumn()
    {
        if (config('database.default') === 'pgsql') {
            return Db::raw("string_to_array(version, '.')::int[]");
        }

        return Db::raw("INET_ATON(SUBSTRING_INDEX(CONCAT(version,'.0.0.0'),'.',4))");
    }

    public function getVersionOptions($value = null, $formData = null): array
    {
        if (!$this->frontapp) {
            return [];
        }

        return $this->frontapp->listVersions($this->id ? (int) $this->id : null);
    }

    public function beforeSave(): void
    {
        $this->extractAssetsFromS3();
    }

    protected function extractAssetsFromS3(): void
    {
        if (!$this->frontapp || !$this->frontapp->is_s3 || !$this->version) {
            return;
        }

        $sFilePath = $this->frontapp->name.'/'.$this->version.'/index.html';

        try {
            if (!Storage::disk('s3')->exists($sFilePath)) {
                return;
            }

            $sHtml = Storage::disk('s3')->get($sFilePath);

            if (!$sHtml) {
                return;
            }

            $obDoc    = new Document($sHtml);
            $arAssets = [];

            // Parse <style /> (inline styles, e.g. CSS @layer order declarations)
            $arStyle = $obDoc->find('head > style');

            if (!empty($arStyle) && is_array($arStyle)) {
                foreach ($arStyle as $obStyle) {
                    $sContent = $obStyle->text();

                    if (!$sContent) {
                        continue;
                    }

                    $arAssets[] = ['src' => null, 'type' => 'style', 'content' => $sContent];
                }
            }

            // Parse <script />
            $arScript = $obDoc->find('head > script');

            if (!empty($arScript) && is_array($arScript)) {
                foreach ($arScript as $obScript) {
                    $arScriptAttr = $obScript->attributes();
                    $sSrc         = array_get($arScriptAttr, 'src');
                    array_forget($arScriptAttr, 'src');

                    if (!$sSrc) {
                        continue;
                    }

                    $arAssets[] = ['src' => $sSrc, 'type' => 'script', 'attrs' => $arScriptAttr];
                }
            }

            // Parse <link />
            $arLink = $obDoc->find('head > link');

            if (!empty($arLink) && is_array($arLink)) {
                foreach ($arLink as $obLink) {
                    $arLinkAttr = $obLink->attributes();
                    $sRel       = array_get($arLinkAttr, 'rel');

                    // Skip rel=icon
                    if ('icon' === $sRel) {
                        continue;
                    }

                    $sHref = array_get($arLinkAttr, 'href');
                    array_forget($arLinkAttr, 'href');

                    if (!$sHref) {
                        continue;
                    }

                    $arAssets[] = ['src' => $sHref, 'type' => 'link', 'attrs' => $arLinkAttr];
                }
            }

            $this->assets = empty($arAssets) ? null : $arAssets;
        } catch (Exception $e) {
            Log::error('Error fetching/parsing S3 index.html for '.$this->frontapp->name.' version '.$this->version.': '.$e->getMessage());
        }
    }
}
