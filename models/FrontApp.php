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
        'path' => 'required',
    ];
    /** @var array */
    public $slugs = [];
    /** @var array */
    public $jsonable = [];
    /** @var array */
    public $fillable = [
        'name',
        'path',
    ];
    /** @var array */
    public $cached = [
        'id',
        'name',
        'path',
    ];
    /** @var array */
    public $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @return array
     */
    public function listVersions()
    {
        if (!$this->path) {
            return [];
        };

        $arPath = [
            rtrim($this->path, '/'),
            'assets',
            $this->name
        ];
        $sPath = plugins_path(join('/', $arPath));
        if (!File::exists($sPath)) {
            return [];
        }

        $arVersionList = array_map(
            function ($sPath) {
                $arParts = explode('/', $sPath);
                return array_pop($arParts);
            },
            File::directories($sPath)
        );

        if (!empty($arVersionList)) {
            return array_combine(array_values($arVersionList), array_values($arVersionList));
        }

        return [];
    }
}
