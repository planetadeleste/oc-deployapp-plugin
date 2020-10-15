<?php namespace PlanetaDelEste\DeployApp\Models;

use Db;
use Model;
use October\Rain\Database\Relations\BelongsTo;

/**
 * Class Version
 *
 * @package PlanetaDelEste\DeployApp\Models
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 *
 * @property integer                   $id
 * @property integer                   $frontapp_id
 * @property string                    $version
 * @property string                    $description
 * @property \October\Rain\Argon\Argon $created_at
 * @property \October\Rain\Argon\Argon $updated_at
 *
 * Relations
 * @property FrontApp                  $frontapp
 * @method BelongsTo|FrontApp          frontapp()
 */
class Version extends Model
{
    /** @var string */
    public $table = 'planetadeleste_deployapp_versions';

    /** @var array */
    public $rules = ['version' => 'required'];

    /** @var array */
    public $fillable = ['version', 'description', 'frontapp_id'];

    /** @var array */
    public $cached = [
        'id',
        'version',
        'description',
        'frontapp_id'
    ];

    /** @var array */
    public $dates = [
        'created_at',
        'updated_at',
    ];
    /** @var array */
    public $belongsTo = [
        'frontapp' => [FrontApp::class]
    ];

    /**
     * @param int $iFrontAppId
     *
     * @return \Illuminate\Database\Eloquent\Model|static|\October\Rain\Database\QueryBuilder|null
     */
    public static function getLatest($iFrontAppId)
    {
        return static::where('frontapp_id', $iFrontAppId)->orderByDesc(static::rawColumn())->first();
    }

    /**
     * @param int $iFrontAppId
     *
     * @return string|null
     */
    public static function getLatestVersion($iFrontAppId)
    {
        $obModel = static::getLatest($iFrontAppId);
        return $obModel ? $obModel->version : null;
    }

    /**
     * @return mixed
     */
    public static function rawColumn()
    {
        return Db::raw("INET_ATON(SUBSTRING_INDEX(CONCAT(version,'.0.0.0'),'.',4))");
    }

    public function getVersionOptions()
    {
        if (!$this->frontapp) {
            return [];
        }

        return $this->frontapp->listVersions();
    }
}
