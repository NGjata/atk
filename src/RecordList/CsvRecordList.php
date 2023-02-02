<?php

namespace Sintattica\Atk\RecordList;

    /**
     * This file is part of the ATK distribution on GitHub.
     * Detailed copyright and licensing information can be found
     * in the doc/COPYRIGHT and doc/LICENSE files which should be
     * included in the distribution.
     *
     *
     * @copyright (c)2000-2004 Ibuildings.nl BV
     * @license http://www.achievo.org/atk/licensing ATK Open Source License
     *
     * @version $Revision: 6323 $
     * $Id$
     */
use Sintattica\Atk\Core\Node;

/**
 * CVS recordlist renderer.
 *
 * @author Patrick van der Velden <patrick@ibuildings.nl>
 */
class CsvRecordList extends CustomRecordList
{
    public $exportCSV = true;

    /**
     * Creates a special Recordlist that can be used for exporting to files or to make it printable.
     *
     * @param Node $node The node to use as definition for the columns.
     * @param array $recordset The records to render
     * @param string $compression Compression technique (bzip / gzip)
     * @param array $suppressList List of attributes from $node that should be ignored
     * @param array $outputparams Key-Value parameters for output. Currently existing:
     *                             filename - the name of the file (without extension .csv)
     * @param bool $titlerow Should titlerow be rendered or not
     * @param bool $decode Should data be decoded or not (for exports)
     */
    public function render(
        $node,
        $recordset,
        $compression = '',
        $suppressList = '',
        $outputparams = [],
        $titlerow = true,
        $decode = false
    ) {
        parent::render(
            $node,
            $recordset,
            '',
            '"',
            '"',
            "\n",
            '1',
            $compression,
            $suppressList,
            $outputparams,
            'csv',
            $titlerow,
            $decode,
            ',',
            null
        );
    }
}
