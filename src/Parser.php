<?php

namespace Wulkanowy\TimetablesListGenerator;

use InvalidArgumentException;
use SpreadsheetReader;

class Parser
{
    /**
     * @var string
     */
    private $filename;

    public function __construct(string $filename)
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('Xls file not found!');
        }

        $this->filename = $filename;
    }

    public function parse() : array
    {
        $reader = new SpreadsheetReader($this->filename);
        $list = [];

        foreach ($reader as $key => $row) {
            if (!isset($row[0]) || !is_numeric($row[0]) || $row[5] === '6') {
                continue;
            }

            $row = array_map(function (string $text) {
                return mb_convert_encoding($text, 'UTF-8');
            }, $row);

            $list[] = [
                'id' => $row[0],
                'rspo' => $row[1],
                'voivodeship' => $row[2],
                'county' => $row[3],
                'community' => $row[4],
                'place' => $row[5],
                'size' => $row[6],
                'type' => $row[7],
                'complexity' => $row[8],
                'name' => $row[9],
                'patron' => $row[10],
                'street' => $row[11],
                'house_number' => $row[12],
                'zip_code' => $row[13],
                'post' => $row[14],
                'telephone' => $row[15],
                'fax' => $row[16],
                'www' => $row[17],
                'public' => $row[18],
                'category' => $row[19],
                'specificity' => $row[20],
                'connection' => $row[21],
                'authority_code' => $row[22],
                'authority_name' => $row[23],
                'authority_voivodeship' => $row[24],
                'authority_county' => $row[25],
                'authority_community' => $row[26],
                'registrant_code' => $row[27],
                'registrant_name' => $row[28],
                'registrant_voivodeship' => $row[29],
                'registrant_county' => $row[30],
                'registrant_community' => $row[31],
                'regon' => $row[32],
                'pupils' => $row[33],
                'girls' => $row[34],
                'pre_school' => $row[35],
                'troops' => $row[36],
                'teachers_full_time' => $row[37],
                'teachers_stos' => $row[38],
                'teachers_no_full_time' => $row[39],
            ];
        }

        return $list;
    }
}
