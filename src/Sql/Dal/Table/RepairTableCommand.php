<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class RepairTableCommand implements \SqlFtw\Sql\MultipleTablesCommand, \SqlFtw\Sql\Dal\Table\DalTableCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName[] */
    private $tables;

    /** @var bool */
    private $local;

    /** @var bool */
    private $quick;

    /** @var bool */
    private $extended;

    /** @var bool */
    private $useFrm;

    /**
     * @param \SqlFtw\Sql\QualifiedName[] $tables
     * @param bool $local
     * @param bool $quick
     * @param bool $extended
     * @param bool $useFrm
     */
    public function __construct(array $tables, bool $local = false, bool $quick = false, bool $extended = false, bool $useFrm = false)
    {
        Check::array($tables, 1);
        Check::itemsOfType($tables, QualifiedName::class);

        $this->tables = $tables;
        $this->local = $local;
        $this->quick = $quick;
        $this->extended = $extended;
        $this->useFrm = $useFrm;
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function isQuick(): bool
    {
        return $this->quick;
    }

    public function isExtended(): bool
    {
        return $this->extended;
    }

    public function useFrm(): bool
    {
        return $this->useFrm;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'REPAIR';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->tables);

        if ($this->quick) {
            $result .= ' QUICK';
        }
        if ($this->extended) {
            $result .= ' EXTENDED';
        }
        if ($this->useFrm) {
            $result .= ' USE_FRM';
        }

        return $result;
    }

}
