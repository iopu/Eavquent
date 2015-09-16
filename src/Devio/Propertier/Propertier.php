<?php
namespace Devio\Propertier;

use Illuminate\Database\Eloquent\Model;
use Devio\Propertier\Finders\PropertyFinder;
use Devio\Propertier\Services\PropertyReader;
use Devio\Propertier\Relations\HasManyProperties;
use Illuminate\Database\Eloquent\Relations\MorphMany;

abstract class Propertier extends Model
{
    /**
     * Caching time in minutes.
     *
     * @var mixed
     */
    protected $cachedColumns = 15;

    /**
     * The cache manager.
     *
     * @var \Illuminate\Cache\Repository|mixed
     */
    protected $cache;

    /**
     * The property reader instance.
     *
     * @var PropertyReader
     */
    protected $reader;

    /**
     * Propertier constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->registerServices();
    }

    /**
     * Will initialize the basic propertier services.
     */
    protected function registerServices()
    {
        $this->reader = new PropertyReader(new PropertyFinder);
        $this->cache = $this->resolveCache();
    }

    /**
     * Relationship to the properties table.
     *
     * @return HasManyProperties
     */
    public function properties()
    {
        $instance = new Property;

        // We are using a self coded relation as there is no foreign key into
        // the properties table. The entity name will be used as a foreign
        // key to find the properties which belong to this entity item.
        return new HasManyProperties(
            $instance->newQuery(), $this, $this->getMorphClass()
        );
    }

    /**
     * Polimorphic relationship to the values table.
     *
     * @return MorphMany
     */
    public function values()
    {
        return $this->morphMany(PropertyValue::class, 'entity');
    }

    /**
     * Will find the PropertyValue raw model instance based on
     * the key passed as argument.
     *
     * @param $key
     *
     * @return null
     */
    public function getPropertyRawValue($key)
    {
        $this->attachValues();

        // This will mix the properties and the values and will decide which values
        // belong to what property. It will work even when setting elements that
        // are not persisted as they will be available into the relationships.
        return $this->reader->properties($this->getRelationValue('properties'))
                            ->values($this->getRelationValue('values'))
                            ->read($key);
    }

    /**
     * Will relate every property with the values it has. This is only useful when
     * querying.
     */
    protected function linkValues()
    {

    }

    /**
     * Will check if the key exists as registerd property.
     *
     * @param $key
     *
     * @return bool
     */
    public function isProperty($key)
    {
        // Checking if the key corresponds to any comlumn in the main entity
        // table. The table columns will be cached every 15 mins as it is
        // really unlikely to change. Caching will reduce the queries.
        if (in_array($key, $this->getTableColumns()))
        {
            return false;
        }

        // $key will be property when it does not belong to any relationship
        // name and it also exists into the entity properties collection.
        // This way it won't interfiere with the model base behaviour.
        return $this->getRelationValue($key)
            ? false
            : ! is_null($this->findProperty($key));
    }

    /**
     * Find a property by its name.
     *
     * @param $name
     *
     * @return mixed
     */
    public function findProperty($name)
    {
        $properties = $this->getRelationValue('properties');

        return (new PropertyFinder)->properties($properties)
                                   ->find($name);
    }

    /**
     * Resolves the cache manager to use.
     *
     * @return \Illuminate\Cache\Repository|mixed
     */
    public function resolveCache()
    {
        return (new CacheResolver)->resolve();
    }

    /**
     * Will return the table columns.
     *
     * @return array
     */
    protected function getTableColumns()
    {
        $key = "propertier.{$this->getMorphClass()}";

        // Will add to a unique cache key the result of querying the model
        // schema columns. When trying to fetch the table columns, this
        // will check if there is cache before running any queries.
        return $this->cache->remember($key, $this->cachedColumns, function ()
        {
            return $this->getConnection()
                        ->getSchemaBuilder()
                        ->getColumnListing($this->getTable());
        });
    }

    /**
     * Overriding magic method.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->isProperty($key))
        {
            return $this->getPropertyRawValue($key);
        }

        return parent::__get($key);
    }
}