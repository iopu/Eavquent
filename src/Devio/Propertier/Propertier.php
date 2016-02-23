<?php

namespace Devio\Propertier;

use Closure;
use Devio\Propertier\Listeners\PartnerSaved;
use Devio\Propertier\Relations\HasMany;
use Devio\Propertier\Relations\MorphMany;
use Devio\Propertier\Listeners\EntitySaved;
use Devio\Propertier\Listeners\EntitySaving;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait Propertier
{
    /**
     * The factory instance.
     *
     * @var Factory
     */
    protected $factory = null;

    /**
     * Dynamic relations array.
     *
     * @var array
     */
    protected $fieldRelations = [];

    /**
     * Model schema columns.
     *
     * @var array
     */
    public static $modelColumns = [];

    /**
     * Booting the trait.
     */
    public static function bootPropertier()
    {
        static::saved(PartnerSaved::class . '@handle');
    }

    /**
     * Polimorphic relationship to the values table.
     *
     * @return MorphMany
     */
    public function fields()
    {
        $table = with($instance = new Value)->getTable();
        list($type, $id) = $this->getMorphs('partner', null, null);

        return new MorphMany(
            $instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $this->getKeyName()
        );
    }

    /**
     * Get if fields can be accessed.
     *
     * @return bool
     */
    public function areFieldsAccessible()
    {
        return $this->getFieldsAutoloading() || ! empty($this->fieldRelations);
    }

    /**
     * Get properties autoloading property.
     *
     * @return bool
     */
    public function getFieldsAutoloading()
    {
        if (property_exists($this, 'fieldsAutoloading')) {
            return $this->fieldsAutoloading;
        }

        return true;
    }

    /**
     * Get the model columns.
     *
     * @return array
     */
    public static function getModelColumns()
    {
        return static::$modelColumns;
    }

    /**
     * Set the model columns.
     *
     * @param $columns
     * @return array
     */
    public static function setModelColumns($columns)
    {
        if (! is_array($columns)) {
            $columns = func_get_args();
        }

        static::$modelColumns = $columns;
    }

    /**
     * Get the factory instance.
     *
     * @return Factory
     */
    public function factory()
    {
        if (! isset($this->factory)) {
            $this->factory = new Factory($this);
        }

        return $this->factory;
    }

    /**
     * Overriding Eloquent getAttribute method will first read a property.
     *
     * @param $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $attribute = parent::getAttribute($key);

        // If the property we are accesing corresponds to a any registered property
        // we will provide the value of this property if any. Otherwise, we will
        // access the parent Eloquent model and return its default behaviour.
        if ($this->areFieldsAccessible() &&
            array_key_exists($key, $this->fieldRelations)
        ) {
            return $this->factory()->get($key, $attribute);
        }

        return $attribute;
    }

    /**
     * Overriding Eloquent setAttribute method will first set a property.
     *
     * @param $key
     * @param $value
     * @return Value
     * @throws \Exception
     */
//    public function setAttribute($key, $value)
//    {
//        $factory = $this->propertierQuery();
//
//        if ($this->isPropertiesRelationAccessible() &&
//            $factory->isProperty($key) &&
//            ! $factory->isModelColumn($key)
//        ) {
//            return $factory->setValue($key, $value);
//        }
//
//        // If the property to set is registered and does not correspond to any
//        // model column we are free to set its value. Otherwise we will let
//        // go the default Eloquent behaviour and return its value if any.
//        return parent::setAttribute($key, $value);
//    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @param $key
     * @param Closure $closure
     * @return array
     */
//    public function getArrayableAttributes()
//    {
//        $attributes = parent::getArrayableAttributes();
//
//        // We will sum an array of properties to the array of attributes in order
//        // to avoid replacing any attribute with a property as original model
//        // attribute names should be considered first instead of property.
//        if ($this->isPropertiesRelationAccessible()) {
//            $attributes = $attributes + $this->propertiesToArray();
//        }
//
//        return $attributes;
//    }
//
//    /**
//     * Convert the properties to an array.
//     *
//     * @return array
//     */
//    public function propertiesToArray()
//    {
//        $values = $this->propertierQuery()->getValues();
//
//        return $this->getArrayableItems($values->toArray());
//    }

    /**
     * @param $key
     * @param Closure $closure
     * @return $this
     */
    public function setFieldRelation($key, Closure $closure)
    {
        $this->fieldRelations[$key] = $closure;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFieldRelations()
    {
        return array_keys($this->fieldRelations);
    }

    /**
     * Handling propertier method calls to the manager class.
     *
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->factory();

        if ($this->areFieldsAccessible()) {
//            // If the method we are trying to call is available in the manager class
//            // we will prevent the default Model call to the Query Builder calling
//            // this method in the Manager class passing this existing instance.
//            if (in_array($method, get_class_methods($factory))) {
//                return call_user_func_array([$factory, $method], $parameters);
//            }

            // As we are defining every field as a relationship and also creating a
            // dynamic method to access this relationship object, we'll check if
            // the method matches any of these relations and return its value.
            if (array_key_exists($method, $this->fieldRelations)) {
                return call_user_func_array($this->fieldRelations[$method], $parameters);
            }
        }

        return parent::__call($method, $parameters);
    }
}
