<?php

use Devio\Propertier\Property;
use Devio\Propertier\PropertyBuilder;
use Devio\Propertier\Properties\StringProperty;
use Devio\Propertier\Properties\IntegerProperty;
use Devio\Propertier\Exceptions\UnresolvedPropertyException;

class PropertyBuilderTest extends TestCase
{

    protected $builder;

    public function setUp()
    {
        parent::setUp();
        $this->builder = new PropertyBuilder;
    }
    
    public function test_it_creates_a_property_by_model()
    {
        $property = new Property(['type' => 'string']);
        $this->assertInstanceOf(StringProperty::class, $this->builder->make($property));
    }
    
    public function test_it_creates_a_property_by_string()
    {
        $this->assertInstanceOf(StringProperty::class, $this->builder->make('string'));
        $this->assertInstanceOf(IntegerProperty::class, $this->builder->make('integer'));
    }

    public function test_it_throws_exception_if_no_property_found()
    {
        $this->setExpectedException(UnresolvedPropertyException::class);
        $this->builder->make('foo');
    }

}