<?php

namespace Devio\Propertier;

use Devio\Propertier\Exceptions\UnresolvedPropertyException;

class Builder
{
    /**
     * Get the right property type class based on the property provided.
     *
     * @param       $property
     * @param array $attributes
     * @return PropertyValue
     * @throws UnresolvedPropertyException
     */
    public function make($property, $attributes = [])
    {
        $class = $this->resolve($property);

        if (! class_exists($class)) {
            throw new UnresolvedPropertyException;
        }

        // Will create a new PropertyValue model based on the property passed as
        // argument. It will also fill the model attributes if they have been
        // provided and relate it to the property, eager loading included.
        $propertyValue = new $class;
        $propertyValue->setRawAttributes($attributes);

        return $propertyValue;
    }

    /**
     * Resolves the property classpath.
     *
     * @param $property
     * @return PropertyAbstract
     * @throws UnresolvedPropertyException
     */
    protected function resolve($property)
    {
        if (is_string($property)) {
            $type = $property;
        } elseif ($property instanceof Property) {
            $type = $property->getAttribute('type');
        } else {
            return null;
        }

        return config("propertier.properties.$type");
    }
}