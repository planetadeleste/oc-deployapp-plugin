<?php

namespace PlanetaDelEste\DeployApp\Models;

use File;
use Kharanenka\Scope\NameField;
use Lovata\Toolbox\Traits\Helpers\TraitCached;
use Model;
use October\Rain\Database\Traits\Validation;

/**
 * Class FrontApp
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 *
 * @property int                       $id
 * @property string                    $name
 * @property string                    $path
 * @property bool                      $resources
 * @property bool                      $is_s3
 * @property \October\Rain\Argon\Argon $created_at
 * @property \October\Rain\Argon\Argon $updated_at
 */
class FrontApp extends Model
{
    use NameField;
    use TraitCached;
    use Validation;

    /**
     * @var string
     */
    public $table = 'planetadeleste_deployapp_frontapps';

    /**
     * @var array
     */
    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /**
     * @var array
     */
    public $translatable = [
        'name',
    ];

    /**
     * @var array
     */
    public $attributeNames = [
        'name' => 'lovata.toolbox::lang.field.name',
    ];

    /**
     * @var array
     */
    public $rules = [
        'name' => 'required',
// 'path' => 'required',
    ];

    /**
     * @var array
     */
    public $fillable = [
        'name',
        'path',
        'resources',
        'is_s3',
    ];

    /**
     * @var array
     */
    public $cached = [
        'id',
        'name',
        'path',
        'resources',
        'is_s3',
    ];

    /**
     * @var array
     */
    public $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = ['resources' => 'boolean', 'is_s3' => 'boolean'];

    /**
     * @return array
     */
    public function listVersions(): array
    {
        $arVersionList = [];

        if ($this->is_s3) {
            try {
                $arDirs        = \Storage::disk('s3')->directories($this->name);
                $arVersionList = array_map(static function ($dir) {
                    return basename($dir);
                }, $arDirs);
            } catch (\Exception $e) {
                \Log::error('Error fetching S3 versions for '.$this->name.': '.$e->getMessage());
            }
        } else {
            if (!$this->path && !$this->resources) {
                return [];
            }

            if ($this->resources) {
                $sPath = resource_path('views/'.$this->name);
            } else {
                $arPath = [
                    rtrim($this->path, '/'),
                    'assets',
                    str_slug($this->name)
                ];

                $sPath = plugins_path(join('/', $arPath));
            }

            if (!File::exists($sPath)) {
                return [];
            }

            $arVersionList = array_map(
                static function ($sPath) {
                    $arParts = explode('/', $sPath);

                    return array_pop($arParts);
                },
                File::directories($sPath)
            );
        }

        $arVersionList = array_filter($arVersionList, static function ($sVersion) {
            return preg_match('/^v?\d+\.\d+\.\d+$/i', $sVersion);
        });

        if (!empty($arVersionList)) {
            usort($arVersionList, 'version_compare');
            $arVersionList = array_reverse($arVersionList);

            return array_combine(array_values($arVersionList), array_values($arVersionList));
        }

        return [];
    }
}
