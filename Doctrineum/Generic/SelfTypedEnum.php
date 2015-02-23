<?php
namespace Doctrineum\Generic;

/**
 * @method static SelfTypedEnum getType($name),
 * @see EnumType::getType
 */
class SelfTypedEnum extends EnumType implements EnumInterface
{
    use EnumTrait;

    /**
     * @param int|float|string|bool|null $enumValue
     */
    public function __construct($enumValue)
    {
        $this->enumValue = $enumValue;
    }

    /**
     * Core idea of self-typed enum.
     * As an enum class returns itself.
     *
     * @return string
     */
    protected static function getEnumClass()
    {
        return static::class;
    }
}
