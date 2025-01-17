<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\RootNode;

class AlterColumnAction implements ColumnAction
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var RootNode|false|null */
    private $setDefault;

    /** @var bool|null */
    private $setVisible;

    /**
     * @param RootNode|false|null $setDefault
     */
    public function __construct(string $name, $setDefault, ?bool $setVisible = null)
    {
        $this->name = $name;
        $this->setDefault = $setDefault;
        $this->setVisible = $setVisible;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return RootNode|false|null
     */
    public function getSetDefault()
    {
        return $this->setDefault;
    }

    public function getSetVisible(): ?bool
    {
        return $this->setVisible;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER COLUMN ' . $formatter->formatName($this->name);

        if ($this->setDefault === false) {
            $result .= ' DROP DEFAULT';
        } elseif ($this->setDefault instanceof Literal) {
            $result .= ' SET DEFAULT ' . $this->setDefault->serialize($formatter);
        } elseif ($this->setDefault !== null) {
            $result .= ' SET DEFAULT (' . $this->setDefault->serialize($formatter) . ')';
        }
        if ($this->setVisible === true) {
            $result .= ' SET VISIBLE';
        } elseif ($this->setVisible === false) {
            $result .= ' SET INVISIBLE';
        }

        return $result;
    }

}
