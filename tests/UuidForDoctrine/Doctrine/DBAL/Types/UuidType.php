<?php

namespace DoctrineExtensionTree\Test\UuidForDoctrine\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UuidType
 */
class UuidType extends Type
{

    const NAME = 'uuid';

    /**
     * {@inheritdoc}
     *
     * @param array $fieldDeclaration
     * @param AbstractPlatform $platform
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getGuidTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|UuidInterface|null $value
     * @param AbstractPlatform $platform
     *
     * @return UuidInterface|null
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof UuidInterface) {
            return $value;
        }

        try {
            $uuid = Uuid::fromString($value);
        } catch (InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, static::NAME);
        }

        return $uuid;
    }

    /**
     * {@inheritdoc}
     *
     * @param UuidInterface|string|null $value
     * @param AbstractPlatform $platform
     *
     * @return string|null
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (
            $value instanceof UuidInterface
            || (
                (is_string($value)
                    || method_exists($value, '__toString'))
                && Uuid::isValid((string) $value)
            )
        ) {
            return (string) $value;
        }

        throw ConversionException::conversionFailed($value, static::NAME);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     *
     * @param AbstractPlatform $platform
     *
     * @return bool
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

}
