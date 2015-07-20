<?php
namespace Doctrineum\Scalar;

use Granam\Scalar\Tools\ToScalar;

trait EnumTrait
{

    /**
     * @var Enum[]
     */
    private static $builtEnums = [];

    /**
     * @var string|int|float|bool|null
     */
    protected $enumValue;

    /**
     * @param string|float|int|bool|null $enumValue
     *
     * @return Enum
     */
    public static function getEnum($enumValue)
    {
        return static::getEnumFromNamespace($enumValue, static::getInnerNamespace());
    }

    protected static function getEnumFromNamespace($enumValue, $namespace)
    {
        $finalEnumValue = static::convertToEnumFinalValue($enumValue);
        if (!static::hasBuiltEnum($finalEnumValue, $namespace)) {
            static::addBuiltEnum(static::createByValue($finalEnumValue), $namespace);
        }

        return static::getBuiltEnum($finalEnumValue, $namespace);
    }

    /**
     * @param mixed $enumValue
     *
     * @return string|float|int|null
     */
    protected static function convertToEnumFinalValue($enumValue)
    {
        return static::convertToScalarOrNull($enumValue);
    }

    /**
     * @param mixed $enumValue
     *
     * @return string|float|int|null
     */
    protected static function convertToScalarOrNull($enumValue)
    {
        try {
            return ToScalar::toScalar($enumValue);
        } catch (\Granam\Scalar\Tools\Exceptions\WrongParameterType $exception) {
            throw new Exceptions\UnexpectedValueToEnum($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    protected static function hasBuiltEnum($enumValue, $namespace)
    {
        return isset(self::$builtEnums[self::createKey($namespace)][self::createKey($enumValue)]);
    }

    /**
     * @param mixed $key
     *
     * @return string
     */
    protected static function createKey($key)
    {
        return serialize($key);
    }

    /**
     * @param EnumInterface $enum
     * @param mixed $namespace
     *
     * @throws Exceptions\EnumIsAlreadyBuilt
     */
    protected static function addBuiltEnum(EnumInterface $enum, $namespace)
    {
        $namespaceKey = self::createKey($namespace);
        $enumKey = self::createKey($enum->getEnumValue());
        if (isset(self::$builtEnums[$namespaceKey][$enumKey])) {
            throw new Exceptions\EnumIsAlreadyBuilt(
                'Enum of namespace key ' . var_export($namespaceKey, true) . ' and enum key ' . var_export($enumKey, true) .
                ' is already registered with enum of class ' . get_class(static::getBuiltEnum($enum->getEnumValue(), $namespace))
            );
        }

        if (!isset(self::$builtEnums[$namespaceKey])) {
            self::$builtEnums[$namespaceKey] = [];
        }

        self::$builtEnums[$namespaceKey][$enumKey] = $enum;
    }

    /**
     * @param mixed $enumValue
     * @param mixed $namespace
     *
     * @return EnumInterface
     */
    protected static function getBuiltEnum($enumValue, $namespace)
    {
        $namespaceKey = self::createKey($namespace);
        $enumKey = self::createKey($enumValue);
        if (!isset(self::$builtEnums[$namespaceKey][$enumKey])) {
            throw new Exceptions\EnumIsNotBuilt(
                'Enum of namespace key ' . var_export($namespaceKey, true) . ' and enum key ' . var_export($enumKey, true) . ' is not registered'
            );
        }

        return self::$builtEnums[self::createKey($namespace)][self::createKey($enumValue)];
    }

    /**
     * @param string|int|float|bool|null $finalEnumValue
     *
     * @return Enum
     */
    protected static function createByValue($finalEnumValue)
    {
        if (!is_scalar($finalEnumValue) && !is_null($finalEnumValue)) {
            throw new Exceptions\UnexpectedValueToEnum('Expected scalar or null, got ' . gettype($finalEnumValue));
        }

        return new static($finalEnumValue);
    }

    /**
     * @return string
     */
    protected static function getInnerNamespace()
    {
        return get_called_class();
    }

    /**
     * @return string (null is casted into empty string!)
     * @see getEnumValue()
     */
    public function __toString()
    {
        return (string)$this->getEnumValue();
    }

    /**
     * @return string|int|float|bool|null
     */
    public function getEnumValue()
    {
        return $this->enumValue;
    }

    /**
     * @throws Exceptions\CanNotBeCloned
     */
    public function __clone()
    {
        throw new Exceptions\CanNotBeCloned('Enum as a singleton can not be cloned. Use same instance everywhere.');
    }

}
