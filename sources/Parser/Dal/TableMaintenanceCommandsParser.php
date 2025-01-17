<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Table\AnalyzeTableDropHistogramCommand;
use SqlFtw\Sql\Dal\Table\AnalyzeTablesCommand;
use SqlFtw\Sql\Dal\Table\AnalyzeTableUpdateHistogramCommand;
use SqlFtw\Sql\Dal\Table\ChecksumTableCommand;
use SqlFtw\Sql\Dal\Table\CheckTableCommand;
use SqlFtw\Sql\Dal\Table\CheckTableOption;
use SqlFtw\Sql\Dal\Table\OptimizeTableCommand;
use SqlFtw\Sql\Dal\Table\RepairTableCommand;
use SqlFtw\Sql\Keyword;

class TableMaintenanceCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ANALYZE [NO_WRITE_TO_BINLOG | LOCAL]
     *     TABLE tbl_name [, tbl_name] ...
     *
     * ANALYZE [NO_WRITE_TO_BINLOG | LOCAL]
     *     TABLE tbl_name
     *     UPDATE HISTOGRAM ON col_name [, col_name] ...
     *     [WITH N BUCKETS]
     *
     * ANALYZE [NO_WRITE_TO_BINLOG | LOCAL]
     *     TABLE tbl_name
     *     DROP HISTOGRAM ON col_name [, col_name] ...
     *
     * @return AnalyzeTablesCommand|AnalyzeTableUpdateHistogramCommand|AnalyzeTableDropHistogramCommand
     */
    public function parseAnalyzeTable(TokenList $tokenList)
    {
        $tokenList->expectKeyword(Keyword::ANALYZE);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES);
        $tables = [];
        do {
            $tables[] = $tokenList->expectQualifiedName();
        } while ($tokenList->hasSymbol(','));

        $columns = $buckets = null;
        if ($tokenList->hasKeywords(Keyword::UPDATE, Keyword::HISTOGRAM, Keyword::ON)) {
            do {
                $columns[] = $tokenList->expectName();
            } while ($tokenList->hasSymbol(','));

            if ($tokenList->hasKeyword(Keyword::WITH)) {
                $buckets = (int) $tokenList->expectUnsignedInt();
                $tokenList->expectKeyword(Keyword::BUCKETS);
            }

            return new AnalyzeTableUpdateHistogramCommand($tables[0], $columns, $buckets, $local);
        } elseif ($tokenList->hasKeywords(Keyword::DROP, Keyword::HISTOGRAM, Keyword::ON)) {
            do {
                $columns[] = $tokenList->expectName();
            } while ($tokenList->hasSymbol(','));

            return new AnalyzeTableDropHistogramCommand($tables[0], $columns, $local);
        }

        return new AnalyzeTablesCommand($tables, $local);
    }

    /**
     * CHECK [TABLE | TABLES] tbl_name [, tbl_name] ... [option] ...
     *
     * option = {
     *     FOR UPGRADE
     *   | QUICK
     *   | FAST
     *   | MEDIUM
     *   | EXTENDED
     *   | CHANGED
     * }
     */
    public function parseCheckTable(TokenList $tokenList): CheckTableCommand
    {
        $tokenList->expectKeyword(Keyword::CHECK);
        $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES);
        $tables = [];
        do {
            $tables[] = $tokenList->expectQualifiedName();
        } while ($tokenList->hasSymbol(','));

        $option = $tokenList->getMultiKeywordsEnum(CheckTableOption::class);

        return new CheckTableCommand($tables, $option);
    }

    /**
     * CHECKSUM TABLE tbl_name [, tbl_name] ... [QUICK | EXTENDED]
     */
    public function parseChecksumTable(TokenList $tokenList): ChecksumTableCommand
    {
        $tokenList->expectKeyword(Keyword::CHECKSUM);
        $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES);
        $tables = [];
        do {
            $tables[] = $tokenList->expectQualifiedName();
        } while ($tokenList->hasSymbol(','));

        $quick = $tokenList->hasKeyword(Keyword::QUICK);
        $extended = $tokenList->hasKeyword(Keyword::EXTENDED);

        return new ChecksumTableCommand($tables, $quick, $extended);
    }

    /**
     * OPTIMIZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     */
    public function parseOptimizeTable(TokenList $tokenList): OptimizeTableCommand
    {
        $tokenList->expectKeyword(Keyword::OPTIMIZE);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES);
        $tables = [];
        do {
            $tables[] = $tokenList->expectQualifiedName();
        } while ($tokenList->hasSymbol(','));

        return new OptimizeTableCommand($tables, $local);
    }

    /**
     * REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     *     [QUICK] [EXTENDED] [USE_FRM]
     */
    public function parseRepairTable(TokenList $tokenList): RepairTableCommand
    {
        $tokenList->expectKeyword(Keyword::REPAIR);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES);
        $tables = [];
        do {
            $tables[] = $tokenList->expectQualifiedName();
        } while ($tokenList->hasSymbol(','));

        $quick = $tokenList->hasKeyword(Keyword::QUICK);
        $extended = $tokenList->hasKeyword(Keyword::EXTENDED);
        $useFrm = $tokenList->hasKeyword(Keyword::USE_FRM);

        return new RepairTableCommand($tables, $local, $quick, $extended, $useFrm);
    }

}
