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
 * Tests for the event observer that applies mappings in real time.
 *
 * @package    local_profilefield_autofill
 * @copyright  2026 David Pesce - Exputo Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profilefield_autofill;

/**
 * Tests for {@see observer}.
 *
 * @covers \local_profilefield_autofill\observer
 */
final class observer_test extends \advanced_testcase {
    /** @var int Source custom profile field id. */
    private $srcid;

    /** @var int Target custom profile field id. */
    private $tgtid;

    /**
     * Create the source and target custom profile fields used by every test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->srcid = $generator->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'pfasrc', 'name' => 'PFA Source',
        ])->id;
        $this->tgtid = $generator->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'pfatgt', 'name' => 'PFA Target',
        ])->id;
    }

    /**
     * Store an enabled mapping.
     *
     * @param string $sourcefield Source field, e.g. profile_field_pfasrc
     * @param string $sourcevalue Value or wildcard pattern to match
     * @param string $targetfield Target field, e.g. profile_field_pfatgt
     * @param string $targetvalue Value to write
     */
    private function add_mapping(
        string $sourcefield,
        string $sourcevalue,
        string $targetfield,
        string $targetvalue
    ): void {
        global $DB;

        $DB->insert_record('local_profilefield_autofill_mapping', (object)[
            'sourcefield' => $sourcefield,
            'sourcevalue' => $sourcevalue,
            'targetfield' => $targetfield,
            'targetvalue' => $targetvalue,
            'enabled' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * The stored value of the target custom profile field, or null when the user
     * has no row for it at all. The distinction matters: the observer used to
     * leave new users with no row rather than an empty one.
     *
     * @param int $userid User id
     * @return string|null
     */
    private function target_value(int $userid): ?string {
        global $DB;

        $data = $DB->get_field(
            'user_info_data',
            'data',
            ['userid' => $userid, 'fieldid' => $this->tgtid]
        );

        return $data === false ? null : $data;
    }

    /**
     * A user created with the source field set, mirroring what an importer using
     * core_user_create_users does: profile fields are saved and then
     * user_created fires.
     *
     * @param string $sourcevalue Value for the source profile field
     * @return int User id
     */
    private function create_user_with_source(string $sourcevalue): int {
        return $this->getDataGenerator()->create_user([
            'profile_field_pfasrc' => $sourcevalue,
        ])->id;
    }

    /**
     * A newly created user has no user_info_data row for the target field, so the
     * observer has to insert one. It previously only ever ran an UPDATE, which
     * matched no rows and silently did nothing.
     */
    public function test_creates_missing_target_row(): void {
        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');

        $userid = $this->create_user_with_source('staff');

        $this->assertSame('STAFF', $this->target_value($userid));
    }

    /**
     * With a row already present the observer updates it in place.
     */
    public function test_updates_existing_target_row(): void {
        global $DB;

        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');

        $userid = $this->getDataGenerator()->create_user()->id;
        $DB->insert_record('user_info_data', (object)[
            'userid' => $userid, 'fieldid' => $this->srcid, 'data' => 'staff', 'dataformat' => 0,
        ]);
        $DB->insert_record('user_info_data', (object)[
            'userid' => $userid, 'fieldid' => $this->tgtid, 'data' => 'OLD', 'dataformat' => 0,
        ]);

        \core\event\user_updated::create_from_userid($userid)->trigger();

        $this->assertSame('STAFF', $this->target_value($userid));
        $this->assertEquals(
            1,
            $DB->count_records(
                'user_info_data',
                ['userid' => $userid, 'fieldid' => $this->tgtid]
            ),
            'the observer should update the existing row rather than add a second one'
        );
    }

    /**
     * Re-running against an already-correct user changes nothing and does not
     * duplicate the row.
     */
    public function test_is_idempotent(): void {
        global $DB;

        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');
        $userid = $this->create_user_with_source('staff');

        \core\event\user_updated::create_from_userid($userid)->trigger();
        \core\event\user_updated::create_from_userid($userid)->trigger();

        $this->assertSame('STAFF', $this->target_value($userid));
        $this->assertEquals(1, $DB->count_records(
            'user_info_data',
            ['userid' => $userid, 'fieldid' => $this->tgtid]
        ));
    }

    /**
     * A mapping onto a standard user field writes to the user record.
     */
    public function test_standard_user_field_target(): void {
        global $DB;

        $this->add_mapping('profile_field_pfasrc', 'staff', 'institution', 'Head Office');

        $userid = $this->create_user_with_source('staff');

        $this->assertSame('Head Office', $DB->get_field('user', 'institution', ['id' => $userid]));
    }

    /**
     * A standard user field can also drive a mapping as the source.
     */
    public function test_standard_user_field_source(): void {
        $this->add_mapping('email', '*@example.com', 'profile_field_pfatgt', 'INTERNAL');

        $userid = $this->getDataGenerator()->create_user(['email' => 'someone@example.com'])->id;

        $this->assertSame('INTERNAL', $this->target_value($userid));
    }

    /**
     * Nothing is written when the source value does not match.
     */
    public function test_no_write_when_source_does_not_match(): void {
        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');

        $userid = $this->create_user_with_source('student');

        $this->assertNull($this->target_value($userid));
    }

    /**
     * A disabled mapping is ignored.
     */
    public function test_disabled_mapping_is_ignored(): void {
        global $DB;

        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');
        $DB->set_field('local_profilefield_autofill_mapping', 'enabled', 0, []);

        $userid = $this->create_user_with_source('staff');

        $this->assertNull($this->target_value($userid));
    }

    /**
     * Pattern matching, exercised through a real event rather than by reaching
     * into the private matcher.
     *
     * The wildcard cases are the regression guard: preg_quote() used to run
     * before '*' was expanded, so '*' became '\*' and the expansion rewrote the
     * escape into '\.*' — a literal dot repeated. A leading-wildcard email
     * pattern then matched only strings of dots before the at sign, and never a
     * real address.
     *
     * @dataProvider pattern_provider
     * @param string $pattern Mapping source value
     * @param string $value Actual value held by the user
     * @param bool $shouldmatch Whether the mapping is expected to apply
     */
    public function test_pattern_matching(string $pattern, string $value, bool $shouldmatch): void {
        $this->add_mapping('profile_field_pfasrc', $pattern, 'profile_field_pfatgt', 'HIT');

        $userid = $this->create_user_with_source($value);

        $this->assertSame(
            $shouldmatch ? 'HIT' : null,
            $this->target_value($userid),
            sprintf('pattern "%s" against "%s"', $pattern, $value)
        );
    }

    /**
     * Cases for {@see test_pattern_matching}.
     *
     * @return array
     */
    public static function pattern_provider(): array {
        return [
            'exact match' => ['staff', 'staff', true],
            'exact mismatch' => ['staff', 'student', false],
            'exact is case insensitive' => ['STAFF', 'staff', true],

            'leading wildcard' => ['*@example.com', 'bob@example.com', true],
            'leading wildcard, wrong domain' => ['*@example.com', 'bob@other.com', false],
            'trailing wildcard' => ['EMP*', 'EMP12345', true],
            'trailing wildcard, no prefix' => ['EMP*', 'CON12345', false],
            'wildcard in the middle' => ['a*z', 'abcz', true],
            'wildcard in the middle, wrong end' => ['a*z', 'abcy', false],
            'lone wildcard matches anything' => ['*', 'anything at all', true],
            'wildcard is case insensitive' => ['*@Example.COM', 'bob@example.com', true],
            'wildcard spans an empty run' => ['a*z', 'az', true],

            // A dot in the pattern must stay a literal dot. The broken expansion
            // turned wildcards into '\.*', so dot-shaped patterns could match
            // where they should not.
            'dot is literal, not any-character' => ['a.c', 'abc', false],
            'dot matches itself' => ['a.c', 'a.c', true],

            // Regex metacharacters in a pattern are literal text.
            'plus sign is literal' => ['a+b', 'a+b', true],
            'plus sign does not repeat' => ['a+b', 'aab', false],
            'parentheses are literal' => ['(x)', '(x)', true],
        ];
    }
}
