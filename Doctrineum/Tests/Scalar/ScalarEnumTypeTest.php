<?php
namespace Doctrineum\Tests\Scalar;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrineum\Scalar\ScalarEnum;
use Doctrineum\Scalar\Enum;
use Doctrineum\Scalar\ScalarEnumType;
use Doctrineum\Tests\Scalar\Helpers\EnumTypes\EnumWithSubNamespaceType;
use Doctrineum\Tests\Scalar\Helpers\EnumTypes\IShouldHaveTypeWordOnEnd;
use Doctrineum\Tests\Scalar\Helpers\EnumTypes\WithoutEnumIsThisType;
use Doctrineum\Tests\Scalar\Helpers\EnumWithSubNamespace;

class ScalarEnumTypeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function can_be_registered()
    {
        $enumTypeClass = $this->getEnumTypeClass();
        $enumTypeClass::registerSelf();
        self::assertTrue(Type::hasType($enumTypeClass::getTypeName()));
        self::assertTrue($enumTypeClass::isRegistered());
    }

    /**
     * @return \Doctrineum\Scalar\ScalarEnumType
     */
    protected function getEnumTypeClass()
    {
        return ScalarEnumType::class;
    }

    /**
     * @test
     * @depends can_be_registered
     */
    public function self_registering_again_returns_false()
    {
        $enumTypeClass = $this->getEnumTypeClass();
        self::assertTrue(Type::hasType($enumTypeClass::getTypeName()));
        self::assertTrue($enumTypeClass::isRegistered());
        self::assertFalse($enumTypeClass::registerSelf());
    }

    /**
     * @test
     * @depends can_be_registered
     */
    public function instance_can_be_obtained()
    {
        $enumTypeClass = $this->getEnumTypeClass();
        $instance = Type::getType($enumTypeClass::getTypeName());
        self::assertInstanceOf($enumTypeClass, $instance);

        return $instance;
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function type_name_is_as_expected(ScalarEnumType $enumType)
    {
        $enumTypeClass = $this->getEnumTypeClass();
        // like self_typed_enum
        $typeName = $this->convertToTypeName($enumTypeClass);
        // like SELF_TYPED_ENUM
        $constantName = strtoupper($typeName);
        self::assertTrue(defined("$enumTypeClass::$constantName"));
        self::assertSame($enumTypeClass::getTypeName(), $typeName);
        self::assertSame($typeName, constant("$enumTypeClass::$constantName"));
        self::assertSame($enumType::getTypeName(), $enumTypeClass::getTypeName());
    }

    /**
     * @param string $className
     *
     * @return string
     */
    private function convertToTypeName($className)
    {
        $withoutType = preg_replace('~Type$~', '', $className);
        $parts = explode('\\', $withoutType);
        $baseClassName = end($parts);
        preg_match_all('~(?<words>[A-Z][^A-Z]+)~', $baseClassName, $matches);
        $concatenated = implode('_', $matches['words']);

        return strtolower($concatenated);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function sql_declaration_is_valid(ScalarEnumType $enumType)
    {

        $platform = $this->getPlatform();
        $sql = $enumType->getSQLDeclaration([], $platform);
        /** @var \PHPUnit_Framework_TestCase $this */
        self::assertSame('VARCHAR(64)', $sql);
    }

    /**
     * @return AbstractPlatform
     */
    protected function getPlatform()
    {
        return \Mockery::mock(AbstractPlatform::class);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function enum_with_null_to_database_value_is_null(ScalarEnumType $enumType)
    {
        $nullEnum = \Mockery::mock(Enum::class);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $nullEnum->shouldReceive('getValue')
            ->once()
            ->andReturn(null);
        /** @var Enum $nullEnum */

        $platform = $this->getPlatform();
        /** @var \PHPUnit_Framework_TestCase $this */
        self::assertNull($enumType->convertToDatabaseValue($nullEnum, $platform));
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function enum_as_database_value_is_string_value_of_that_enum(ScalarEnumType $enumType)
    {
        $enum = \Mockery::mock(Enum::class);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $enum->shouldReceive('getValue')
            ->once()
            ->andReturn($value = 'foo');

        $platform = $this->getPlatform();
        /** @var Enum $enum */
        self::assertSame($value, $enumType->convertToDatabaseValue($enum, $platform));
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function null_to_php_value_creates_enum_with_null_value(ScalarEnumType $enumType)
    {

        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue(null, $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertNull($enum->getValue());
    }

    /**
     * @return \Doctrineum\Scalar\ScalarEnum
     */
    protected function getRegisteredEnumClass()
    {
        return ScalarEnum::class;
    }

    /**
     * conversion to PHP tests
     */

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function string_to_php_value_is_enum_with_that_string(ScalarEnumType $enumType)
    {

        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($string = 'foo', $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($string, $enum->getValue());
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function empty_string_to_php_value_is_enum_with_that_empty_string(ScalarEnumType $enumType)
    {

        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($emptyString = '', $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($emptyString, $enum->getValue());
    }

    /**
     * The Enum class does NOT cast non-string scalars into string (integers, floats etc).
     * (But saving the value into database and pulling it back probably will.)
     *
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function integer_to_php_value_is_enum_with_that_integer(ScalarEnumType $enumType)
    {

        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($integer = 12345, $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($integer, $enum->getValue());
    }

    /**
     * The Enum class does NOT cast non-string scalars into string (integers, floats etc).
     * (But saving the value into database and pulling it back probably will.)
     *
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function zero_integer_to_php_value_is_enum_with_that_zero_integer(ScalarEnumType $enumType)
    {

        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($zeroInteger = 0, $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($zeroInteger, $enum->getValue());
    }

    /**
     * The Enum class does NOT cast non-string scalars into string (integers, floats etc).
     * (But saving the value into database and pulling it back probably will.)
     *
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function float_to_php_value_is_enum_with_that_float(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($float = 12345.6789, $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($float, $enum->getValue());
    }

    /**
     * The Enum class does NOT cast non-string scalars into string (integers, floats etc).
     * (But saving the value into database and pulling it back probably will.)
     *
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function zero_float_to_php_value_is_enum_with_that_zero_float(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($zeroFloat = 0.0, $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($zeroFloat, $enum->getValue());
    }

    /**
     * The Enum class does NOT cast non-string scalars into string (integers, floats etc).
     * (But saving the value into database and pulling it back probably will.)
     *
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function false_to_php_value_is_enum_with_that_false(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($false = false, $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($false, $enum->getValue());
    }

    /**
     * The Enum class does NOT cast non-string scalars into string (integers, floats etc).
     * (But saving the value into database and pulling it back probably will.)
     *
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function true_to_php_value_is_enum_with_that_true(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue($true = true, $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($true, $enum->getValue());
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function object_with_to_string_to_php_value_is_enum_with_that_string(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enum = $enumType->convertToPHPValue(new WithToStringTestObject($value = 'foo'), $platform);
        self::assertInstanceOf($this->getRegisteredEnumClass(), $enum);
        self::assertSame($value, $enum->getValue());
        self::assertSame($value, (string)$enum);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\UnexpectedValueToEnum
     */
    public function array_to_php_value_cause_exception(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enumType->convertToPHPValue([], $platform);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\UnexpectedValueToEnum
     */
    public function resource_to_php_value_cause_exception(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enumType->convertToPHPValue(tmpfile(), $platform);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\UnexpectedValueToEnum
     */
    public function object_to_php_value_cause_exception(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enumType->convertToPHPValue(new \stdClass(), $platform);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\UnexpectedValueToEnum
     */
    public function callback_to_php_value_cause_exception(ScalarEnumType $enumType)
    {
        $platform = $this->getPlatform();
        $enumType->convertToPHPValue(function () {
        }, $platform);
    }

    /**
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\UnexpectedValueToDatabaseValue
     */
    public function conversion_of_non_object_to_database_cause_exception()
    {
        $enumTypeClass = $this->getEnumTypeClass();
        $enumType = Type::getType($enumTypeClass::getTypeName());
        $enumType->convertToDatabaseValue('foo', $this->getPlatform());
    }

    /**
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\UnexpectedValueToDatabaseValue
     */
    public function conversion_of_non_enum_to_database_cause_exception()
    {
        $enumTypeClass = $this->getEnumTypeClass();
        $enumType = Type::getType($enumTypeClass::getTypeName());
        $enumType->convertToDatabaseValue(new \stdClass(), $this->getPlatform());
    }

    /**
     * @test
     * @depends instance_can_be_obtained
     */
    public function I_get_same_enum_type_name_as_enum_type_instance_name()
    {
        $enumTypeClass = $this->getEnumTypeClass();
        $enumType = Type::getType($enumTypeClass::getTypeName());
        self::assertSame($enumTypeClass::getTypeName(), $enumType->getName());
    }

    /**
     * @test
     * @depends instance_can_be_obtained
     */
    public function It_requires_sql_comment_hint()
    {
        $enumTypeClass = $this->getEnumTypeClass();
        $enumType = Type::getType($enumTypeClass::getTypeName());
        self::assertTrue($enumType->requiresSQLCommentHint($this->getPlatform()));
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @return ScalarEnumType
     *
     * @test
     * @depends instance_can_be_obtained
     */
    public function I_can_register_subtype(ScalarEnumType $enumType)
    {
        self::assertTrue($enumType::addSubTypeEnum($this->getSubTypeEnumClass(), '~foo~'));
        self::assertTrue($enumType::hasSubTypeEnum($this->getSubTypeEnumClass()));

        return $enumType;
    }

    /**
     * SUBTYPE TESTS
     */

    /**
     * @return string|TestSubTypeScalarEnum
     */
    protected function getSubTypeEnumClass()
    {
        return TestSubTypeScalarEnum::getClass();
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends I_can_register_subtype
     */
    public function can_remove_subtype(ScalarEnumType $enumType)
    {
        /**
         * The subtype is unregistered because of tearDown clean up
         * @see EnumTypeTestTrait::tearDown
         */
        self::assertFalse($enumType::hasSubTypeEnum($this->getSubTypeEnumClass()));
        self::assertTrue($enumType::addSubTypeEnum($this->getSubTypeEnumClass(), '~foo~'));
        self::assertTrue($enumType::removeSubTypeEnum($this->getSubTypeEnumClass()));
        self::assertFalse($enumType::hasSubTypeEnum($this->getSubTypeEnumClass()));
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends I_can_register_subtype
     * @expectedException \Doctrineum\Scalar\Exceptions\SubTypeEnumIsNotRegistered
     */
    public function I_can_not_remove_not_registered_subtype(ScalarEnumType $enumType)
    {
        /**
         * The subtype is unregistered because of tearDown clean up
         * @see EnumTypeTestTrait::tearDown
         */
        self::assertFalse($enumType::hasSubTypeEnum($this->getSubTypeEnumClass()));
        self::assertTrue($enumType::addSubTypeEnum($this->getSubTypeEnumClass(), '~foo~'));
        self::assertTrue($enumType::removeSubTypeEnum($this->getSubTypeEnumClass()));
        self::assertTrue($enumType::removeSubTypeEnum($this->getSubTypeEnumClass())); // twice the same
    }

    /**
     * @param ScalarEnumType $subType
     *
     * @test
     * @depends I_can_register_subtype
     */
    public function subtype_returns_proper_enum(ScalarEnumType $subType)
    {
        self::assertTrue($subType::addSubTypeEnum($this->getSubTypeEnumClass(), $regexp = '~some specific string~'));
        /** @var AbstractPlatform $abstractPlatform */
        $abstractPlatform = $this->getPlatform();
        $matchingValueToConvert = 'A string with some specific string inside.';
        self::assertRegExp($regexp, $matchingValueToConvert);
        /**
         * Used TestSubTypeEnum returns as an "enum" the given value, which is $valueToConvert in this case,
         * @see \Doctrineum\Tests\Scalar\TestSubTypeEnum::getEnum
         */
        $enumFromSubType = $subType->convertToPHPValue($matchingValueToConvert, $abstractPlatform);
        self::assertInstanceOf($this->getSubTypeEnumClass(), $enumFromSubType);
        self::assertSame($matchingValueToConvert, (string)$enumFromSubType);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends I_can_register_subtype
     */
    public function default_enum_is_given_if_subtype_does_not_match(ScalarEnumType $enumType)
    {
        self::assertTrue($enumType::addSubTypeEnum($this->getSubTypeEnumClass(), $regexp = '~some specific string~'));
        /** @var AbstractPlatform $abstractPlatform */
        $abstractPlatform = $this->getPlatform();
        $nonMatchingValueToConvert = 'A string without that specific string.';
        self::assertNotRegExp($regexp, $nonMatchingValueToConvert);
        /**
         * Used TestSubTypeEnum returns as an "enum" the given value, which is $valueToConvert in this case,
         * @see \Doctrineum\Tests\Scalar\TestSubTypeEnum::getEnum
         */
        $enum = $enumType->convertToPHPValue($nonMatchingValueToConvert, $abstractPlatform);
        self::assertNotSame($nonMatchingValueToConvert, $enum);
        self::assertInstanceOf(Enum::class, $enum);
        self::assertSame($nonMatchingValueToConvert, (string)$enum);
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\SubTypeEnumIsAlreadyRegistered
     */
    public function registering_same_subtype_again_throws_exception(ScalarEnumType $enumType)
    {
        self::assertFalse($enumType::hasSubTypeEnum($this->getSubTypeEnumClass()));
        self::assertTrue($enumType::addSubTypeEnum($this->getSubTypeEnumClass(), '~foo~'));
        // registering twice - should thrown an exception
        $enumType::addSubTypeEnum($this->getSubTypeEnumClass(), '~foo~');
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\SubTypeEnumClassNotFound
     */
    public function I_am_stopped_on_registering_of_non_existing_type(ScalarEnumType $enumType)
    {
        $enumType::addSubTypeEnum('whoAmI', '~foo~');
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\SubTypeEnumHasToBeEnum
     */
    public function registering_invalid_subtype_class_throws_exception(ScalarEnumType $enumType)
    {
        $enumType::addSubTypeEnum('stdClass', '~foo~');
    }

    /**
     * @param ScalarEnumType $enumType
     *
     * @test
     * @depends instance_can_be_obtained
     * @expectedException \Doctrineum\Scalar\Exceptions\InvalidRegexpFormat
     * @expectedExceptionMessage The given regexp is not enclosed by same delimiters and therefore is not valid: 'foo~'
     */
    public function registering_subtype_with_invalid_regexp_throws_exception(ScalarEnumType $enumType)
    {
        $enumType::addSubTypeEnum(
            $this->getSubTypeEnumClass(),
            'foo~' // missing opening delimiter
        );
    }

    /**
     * @test
     *
     * @depends can_be_registered
     */
    public function can_register_another_enum_type()
    {
        $anotherEnumType = $this->getAnotherEnumTypeClass();
        if (!$anotherEnumType::isRegistered()) {
            self::assertTrue($anotherEnumType::registerSelf());
        } else {
            self::assertFalse($anotherEnumType::registerSelf());
        }

        self::assertTrue($anotherEnumType::isRegistered());
        self::assertTrue(Type::hasType($anotherEnumType::getTypeName()));
    }

    /**
     * @return string|TestAnotherScalarEnumType
     */
    protected function getAnotherEnumTypeClass()
    {
        return TestAnotherScalarEnumType::getClass();
    }

    /**
     * @test
     *
     * @depends can_register_another_enum_type
     */
    public function different_types_with_same_subtype_regexp_distinguish_them()
    {
        /** @var ScalarEnumType $enumTypeClass */
        $enumTypeClass = $this->getEnumTypeClass();
        if ($enumTypeClass::hasSubTypeEnum($this->getSubTypeEnumClass())) {
            $enumTypeClass::removeSubTypeEnum($this->getSubTypeEnumClass());
        }
        $enumTypeClass::addSubTypeEnum($this->getSubTypeEnumClass(), $regexp = '~searching pattern~');

        $anotherEnumTypeClass = $this->getAnotherEnumTypeClass();
        if ($anotherEnumTypeClass::hasSubTypeEnum($this->getAnotherSubTypeEnumClass())) {
            $anotherEnumTypeClass::removeSubTypeEnum($this->getAnotherSubTypeEnumClass());
        }
        // regexp is same, sub-type is not
        $anotherEnumTypeClass::addSubTypeEnum($this->getAnotherSubTypeEnumClass(), $regexp);

        $value = 'some string fitting to searching pattern';
        self::assertRegExp($regexp, $value);

        $enumType = Type::getType($enumTypeClass::getTypeName());
        $enumSubType = $enumType->convertToPHPValue($value, $this->getPlatform());
        self::assertInstanceOf($this->getSubTypeEnumClass(), $enumSubType);
        self::assertSame($value, "$enumSubType");

        $anotherEnumType = Type::getType($anotherEnumTypeClass::getTypeName());
        $anotherEnumSubType = $anotherEnumType->convertToPHPValue($value, $this->getPlatform());
        self::assertInstanceOf($this->getSubTypeEnumClass(), $enumSubType);
        self::assertSame($value, "$anotherEnumSubType");

        // registered sub-types were different, just regexp was the same - let's test if they are kept separately
        self::assertNotSame($enumSubType, $anotherEnumSubType);
    }

    /**
     * @return string|TestAnotherSubTypeScalarEnum
     */
    protected function getAnotherSubTypeEnumClass()
    {
        return TestAnotherSubTypeScalarEnum::getClass();
    }

    /**
     * @test
     * @depends can_be_registered
     */
    public function repeated_self_registration_returns_false()
    {
        self::assertFalse(ScalarEnumType::registerSelf());
    }

    /** @test */
    public function can_use_subtype()
    {
        ScalarEnumType::addSubTypeEnum($this->getSubTypeEnumClass(), $pattern = '~foo~');
        self::assertRegExp($pattern, $enumValue = 'foo bar baz');
        $enumBySubType = ScalarEnumType::getType(ScalarEnumType::SCALAR_ENUM)->convertToPHPValue($enumValue, $this->getPlatform());
        self::assertInstanceOf($this->getSubTypeEnumClass(), $enumBySubType);
    }

    /**
     * @test
     */
    public function I_can_use_enum_type_from_sub_namespace()
    {
        EnumWithSubNamespaceType::registerSelf();
        $enum = EnumWithSubNamespaceType::getType(EnumWithSubNamespaceType::getTypeName())
            ->convertToPHPValue('foo', $this->getPlatform());
        self::assertInstanceOf(EnumWithSubNamespace::getClass(), $enum);
    }

    /**
     * @test
     * @expectedException \Doctrineum\Scalar\Exceptions\EnumClassNotFound
     */
    public function I_am_stopped_by_exception_on_conversion_to_unknown_enum()
    {
        WithoutEnumIsThisType::registerSelf();
        $type = WithoutEnumIsThisType::getType(WithoutEnumIsThisType::getTypeName());
        $type->convertToPHPValue('foo', $this->getPlatform());
    }

    /**
     * @test
     * @expectedException \Doctrineum\Scalar\Exceptions\CouldNotDetermineEnumClass
     */
    public function I_can_not_use_type_with_unexpected_name_structure()
    {
        IShouldHaveTypeWordOnEnd::registerSelf();
        $type = IShouldHaveTypeWordOnEnd::getType(IShouldHaveTypeWordOnEnd::getTypeName());
        $type->convertToPHPValue('foo', $this->getPlatform());
    }

    /**
     * @test
     * @depends can_be_registered
     * @expectedException \Doctrineum\Scalar\Exceptions\TypeNameOccupied
     */
    public function I_can_not_silently_rewrite_type_by_same_name()
    {
        IAmUsingOccupiedName::registerSelf();
    }

    /**
     * This is called after every test
     */
    protected function tearDown()
    {
        \Mockery::close();

        $enumTypeClass = $this->getEnumTypeClass();
        if (Type::hasType($enumTypeClass::getTypeName())) {
            $enumType = Type::getType($enumTypeClass::getTypeName());
            /** @var ScalarEnumType $enumType */
            if ($enumType::hasSubTypeEnum($this->getSubTypeEnumClass())) {
                self::assertTrue($enumType::removeSubTypeEnum($this->getSubTypeEnumClass()));
            }
        }
    }

}

/** inner */
class TestSubTypeScalarEnum extends ScalarEnum
{

}

class TestAnotherSubTypeScalarEnum extends ScalarEnum
{

}

class TestAnotherScalarEnumType extends ScalarEnumType
{

}

class IAmUsingOccupiedName extends ScalarEnumType
{
    public static function getTypeName()
    {
        // Doctrineum\Scalar\EnumType = EnumType
        $baseClassName = preg_replace('~(\w+\\\)*(\w+)~', '$2', ScalarEnumType::class);
        // EnumType = Enum
        $baseTypeName = preg_replace('~Type$~', '', $baseClassName);

        // FooBarEnum = Foo_Bar_Enum = foo_bar_enum
        return strtolower(preg_replace('~(\w)([A-Z])~', '$1_$2', $baseTypeName));
    }
}
