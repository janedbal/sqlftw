<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use function explode;

class QualifiedName implements ColumnIdentifier, FunctionIdentifier, TableIdentifier
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $schema;

    public function __construct(string $name, ?string $schema = null)
    {
        $this->name = $name;
        $this->schema = $schema;
    }

    public function coalesce(string $schema): self
    {
        return $this->schema !== null ? $this : new self($this->name, $schema);
    }

    /**
     * @param self[] $names
     * @return string[]
     */
    public static function uniqueSchemas(array $names, string $currentSchema): array
    {
        $schemas = [];
        foreach ($names as $name) {
            $schema = $name->getSchema() ?? $currentSchema;
            $schemas[$schema] = $schema;
        }

        return $schemas;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * @return array{string, string|null}
     */
    public function toArray(): array
    {
        return [$this->name, $this->schema];
    }

    public function getFullName(): string
    {
        return $this->schema !== null
            ? $this->schema . '.' . $this->name
            : $this->name;
    }

    public function equals(string $fullName): bool
    {
        [$schema, $name] = explode('.', $fullName);

        return $this->schema === $schema && $this->name === $name;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->schema !== null
            ? $formatter->formatName($this->schema) . '.' . $formatter->formatName($this->name)
            : $formatter->formatName($this->name);
    }

}
