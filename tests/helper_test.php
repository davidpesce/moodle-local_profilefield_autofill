<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the helper, chiefly CSV parsing.
 *
 * @package    local_profilefield_autofill
 * @copyright  2026 David Pesce - Exputo Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profilefield_autofill;

/**
 * Tests for {@see helper}.
 *
 * @covers \local_profilefield_autofill\helper
 */
final class helper_test extends \advanced_testcase {
    /**
     * CSV content is parsed into rows.
     *
     * @dataProvider csv_provider
     * @param string $content Raw CSV content
     * @param string $delimiter Delimiter character
     * @param array $expected Expected rows
     */
    public function test_parse_csv_content(string $content, string $delimiter, array $expected): void {
        $this->assertSame($expected, helper::parse_csv_content($content, $delimiter, 'UTF-8', false));
    }

    /**
     * Cases for {@see test_parse_csv_content}.
     *
     * The embedded-newline cases are the regression guard. The previous
     * implementation split the content on "\n" before parsing each line, which
     * survived a quoted newline only in the first column; anywhere else it tore
     * one record into two malformed rows.
     *
     * @return array
     */
    public static function csv_provider(): array {
        return [
            'single row' => [
                "a,b,c,d\n", ',', [['a', 'b', 'c', 'd']],
            ],
            'two rows' => [
                "a,b,c,d\ne,f,g,h\n", ',', [['a', 'b', 'c', 'd'], ['e', 'f', 'g', 'h']],
            ],
            'no trailing newline' => [
                "a,b,c,d", ',', [['a', 'b', 'c', 'd']],
            ],
            'CRLF line endings' => [
                "a,b,c,d\r\ne,f,g,h\r\n", ',', [['a', 'b', 'c', 'd'], ['e', 'f', 'g', 'h']],
            ],
            'blank lines are skipped' => [
                "a,b,c,d\n\n\ne,f,g,h\n", ',', [['a', 'b', 'c', 'd'], ['e', 'f', 'g', 'h']],
            ],
            'quoted delimiter' => [
                "\"a,1\",b,c,d\n", ',', [['a,1', 'b', 'c', 'd']],
            ],
            'escaped quotes' => [
                "\"say \"\"hi\"\"\",b,c,d\n", ',', [['say "hi"', 'b', 'c', 'd']],
            ],
            'newline in first field' => [
                "\"l1\nl2\",b,c,d\n", ',', [["l1\nl2", 'b', 'c', 'd']],
            ],
            'newline in middle field' => [
                "a,\"l1\nl2\",c,d\n", ',', [['a', "l1\nl2", 'c', 'd']],
            ],
            'newline in last field' => [
                "a,b,c,\"l1\nl2\"\n", ',', [['a', 'b', 'c', "l1\nl2"]],
            ],
            'newline mid-row then a further row' => [
                "a,\"l1\nl2\",c,d\ne,f,g,h\n", ',',
                [['a', "l1\nl2", 'c', 'd'], ['e', 'f', 'g', 'h']],
            ],
            'semicolon delimiter' => [
                "a;b;c;d\n", ';', [['a', 'b', 'c', 'd']],
            ],
            'pipe delimiter' => [
                "a|b|c|d\n", '|', [['a', 'b', 'c', 'd']],
            ],
            'tab delimiter' => [
                "a\tb\tc\td\n", "\t", [['a', 'b', 'c', 'd']],
            ],
            // The plugin's own export is written with a byte order mark, so a file
            // exported and re-imported must not carry it into the first cell.
            'UTF-8 byte order mark' => [
                "\xEF\xBB\xBFa,b,c,d\n", ',', [['a', 'b', 'c', 'd']],
            ],
        ];
    }

    /**
     * The tab delimiter arrives from the form as a literal backslash-t.
     */
    public function test_parse_csv_content_accepts_escaped_tab_delimiter(): void {
        $rows = helper::parse_csv_content("a\tb\tc\td\n", '\t', 'UTF-8', false);

        $this->assertSame([['a', 'b', 'c', 'd']], $rows);
    }

    /**
     * The header row is dropped when the caller says there is one.
     */
    public function test_parse_csv_content_removes_header(): void {
        $content = "sourcefield,sourcevalue,targetfield,targetvalue\nemail,*@x.com,city,Boston\n";

        $rows = helper::parse_csv_content($content, ',', 'UTF-8', true);

        $this->assertSame([['email', '*@x.com', 'city', 'Boston']], $rows);
    }

    /**
     * Values that a spreadsheet would treat as a formula are neutralised on export.
     *
     * @dataProvider formula_provider
     * @param string $value Cell value
     * @param bool $shouldescape Whether the value must be prefixed
     */
    public function test_escape_csv_formula(string $value, bool $shouldescape): void {
        $escaped = helper::escape_csv_formula($value);

        if ($shouldescape) {
            $this->assertSame("'" . $value, $escaped);
        } else {
            $this->assertSame($value, $escaped);
        }
    }

    /**
     * Cases for {@see test_escape_csv_formula}.
     *
     * These mirror the rules in \core\dataformat::escape_spreadsheet_formula(),
     * which the plugin cannot call directly because it postdates the Moodle 4.5.0
     * this plugin supports.
     *
     * @return array
     */
    public static function formula_provider(): array {
        return [
            'equals' => ['=1+1', true],
            'plus' => ['+1', true],
            'minus' => ['-1', true],
            'at sign' => ['@SUM(A1)', true],
            'command injection shape' => ['=cmd|\' /c calc\'!A1', true],
            'leading whitespace then formula' => ['   =1+1', true],
            'ordinary text' => ['Boston', false],
            'email pattern' => ['*@example.com', false],
            'lone dash is a null placeholder' => ['-', false],
            'empty' => ['', false],
            'whitespace only' => ['   ', false],
        ];
    }
}
