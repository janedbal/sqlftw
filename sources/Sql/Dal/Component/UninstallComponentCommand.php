<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Component;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class UninstallComponentCommand extends Statement implements ComponentCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $components;

    /**
     * @param non-empty-array<string> $components
     */
    public function __construct(array $components)
    {
        $this->components = $components;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'UNINSTALL COMPONENT ' . $formatter->formatNamesList($this->components);
    }

}
