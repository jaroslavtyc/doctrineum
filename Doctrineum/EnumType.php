<?php
namespace Doctrineum;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumType extends Type
{
    const TYPE = 'enum';
    const ENUM_CLASS = Enum::class;
    const VARCHAR_LENGTH = 64;

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if ($platform instanceof DoctrineumPlatform) {
            return $platform->getEnumSQLDeclaration();
        }
        return 'VARCHAR(' . $this->getDefaultLength($platform) . ')';
    }

    /**
     * @param AbstractPlatform $platform
     * @return int
     */
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return self::VARCHAR_LENGTH;
    }

    /**
     * Convert enum instance to database string value
     *
     * @param Enum $value
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @return string
     * @throws Exceptions\InvalidArgument
     * @throws Exceptions\Logic
     */
    public function convertToDatabaseValue(Enum $value = null, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return null;
        }

        return $value->getValue();
    }

    /**
     * Convert database string value to Enum instance
     *
     * @param string|null $value
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @return Enum|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        // Enum class can be defined by platform
        if ($platform instanceof DoctrineumPlatform) {
            return $platform->convertToEnum($value);
        }

        return $this->convertToEnum($value);
    }

    /**
     * @param $value
     * @return Enum|null
     */
    protected function convertToEnum($value)
    {
        if (is_null($value)) {
            return null;
        }

        $enumClass = self::ENUM_CLASS;
        /** @var Enum $enumClass */
        return $enumClass::get($value);
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * If this Doctrine Type maps to an already mapped database type,
     * reverse schema engineering can't take them apart. You need to mark
     * one of those types as commented, which will have Doctrine use an SQL
     * comment to typehint the actual Doctrine Type.
     *
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @return boolean
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}