<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\LogfileGroup;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;
use SqlFtw\Sql\Expression\SizeLiteral;
use SqlFtw\Sql\Statement;

class CreateLogfileGroupCommand extends Statement implements LogfileGroupCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var StorageEngine|null */
    private $engine;

    /** @var string */
    private $undoFile;

    /** @var SizeLiteral|null */
    private $initialSize;

    /** @var SizeLiteral|null */
    private $undoBufferSize;

    /** @var SizeLiteral|null */
    private $redoBufferSize;

    /** @var int|null */
    private $nodeGroup;

    /** @var bool */
    private $wait;

    /** @var string|null */
    private $comment;

    public function __construct(
        string $name,
        ?StorageEngine $engine,
        string $undoFile,
        ?SizeLiteral $initialSize = null,
        ?SizeLiteral $undoBufferSize = null,
        ?SizeLiteral $redoBufferSize = null,
        ?int $nodeGroup = null,
        bool $wait = false,
        ?string $comment = null
    )
    {
        $this->name = $name;
        $this->engine = $engine;
        $this->undoFile = $undoFile;
        $this->initialSize = $initialSize;
        $this->undoBufferSize = $undoBufferSize;
        $this->redoBufferSize = $redoBufferSize;
        $this->nodeGroup = $nodeGroup;
        $this->wait = $wait;
        $this->comment = $comment;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): ?StorageEngine
    {
        return $this->engine;
    }

    public function getUndoFile(): string
    {
        return $this->undoFile;
    }

    public function getInitialSize(): ?SizeLiteral
    {
        return $this->initialSize;
    }

    public function getUndoBufferSize(): ?SizeLiteral
    {
        return $this->undoBufferSize;
    }

    public function getRedoBufferSize(): ?SizeLiteral
    {
        return $this->redoBufferSize;
    }

    public function getNodeGroup(): ?int
    {
        return $this->nodeGroup;
    }

    public function wait(): bool
    {
        return $this->wait;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE LOGFILE GROUP ' . $formatter->formatName($this->name) . ' ADD UNDOFILE ' . $formatter->formatString($this->undoFile);
        if ($this->initialSize !== null) {
            $result .= ' INITIAL_SIZE = ' . $this->initialSize->serialize($formatter);
        }
        if ($this->undoBufferSize !== null) {
            $result .= ' UNDO_BUFFER_SIZE = ' . $this->undoBufferSize->serialize($formatter);
        }
        if ($this->redoBufferSize !== null) {
            $result .= ' REDO_BUFFER_SIZE = ' . $this->redoBufferSize->serialize($formatter);
        }
        if ($this->nodeGroup !== null) {
            $result .= ' NODEGROUP = ' . $this->nodeGroup;
        }
        if ($this->wait) {
            $result .= ' WAIT';
        }
        if ($this->comment !== null) {
            $result .= ' COMMENT = ' . $formatter->formatString($this->comment);
        }
        if ($this->engine !== null) {
            $result .= ' ENGINE = ' . $this->engine->serialize($formatter);
        }

        return $result;
    }

}
