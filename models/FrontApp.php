<?php namespace PlanetaDelEste\DeployApp\Models;

use File;
use Model;
use October\Rain\Database\Traits\Validation;
use Kharanenka\Scope\NameField;
use Lovata\Toolbox\Traits\Helpers\TraitCached;

/**
 * Class FrontApp
 *
 * @package PlanetaDelEste\DeployApp\Models
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 *
 * @property integer                   $id
 * @property string                    $name
 * @property string                    $path
 * @property boolean                   $resources
 * @property \October\Rain\Argon\Argon $created_at
 * @property \October\Rain\Argon\Argon $updated_at
 */
class FrontApp extends Model
{
    use Validation;
    use NameField;
    use TraitCached;

    /** @var string */
    public $table = 'planetadeleste_deployapp_frontapps';

    /** @var array */
    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /** @var array */
    public $translatable = [
        'name',
    ];

    /** @var array */
    public $attributeNames = [
        'name' => 'lovata.toolbox::lang.field.name',
    ];

    /** @var array */
    public $rules = [
        'name' => 'required',
//        'path' => 'required',
    ];

    /** @var array */
    public $fillable = [
        'name',
        'path',
        'resources',
    ];

    /** @var array */
    public $cached = [
        'id',
        'name',
        'path',
        'resources',
    ];

    /** @var array */
    public $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = ['resources' => 'boolean'];

    /**
     * @return array
     */
    public function listVersions(): array
    {
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

        $arCurrentVersionList = Version::where('frontapp_id', $this->id)->lists('version');
        $arVersionList = array_map(
            function ($sPath) {
                $arParts = explode('/', $sPath);
                return array_pop($arParts);
            },
            File::directories($sPath)
        );
        $arVersionList = array_diff($arVersionList, $arCurrentVersionList);

        if (!empty($arVersionList)) {
            usort($arVersionList, 'version_compare');
            $arVersionList = array_reverse($arVersionList);
            return array_combine(array_values($arVersionList), array_values($arVersionList));
        }

        return [];
    }
}
