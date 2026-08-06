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
 * Tests for the scheduled task that applies mappings in bulk.
 *
 * @package    local_profilefield_autofill
 * @copyright  2026 David Pesce - Exputo Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profilefield_autofill\task;

/**
 * Tests for {@see apply_mappings}.
 *
 * @covers \local_profilefield_autofill\task\apply_mappings
 */
final class apply_mappings_test extends \advanced_testcase {
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
     * Create a user holding a source value, without letting the observer act on
     * it, so that each test exercises the task alone. The source row is written
     * after creation and no update event is fired.
     *
     * @param string $sourcevalue Value for the source profile field
     * @param array $record Extra fields for the user record
     * @return int User id
     */
    private function create_user_with_source(string $sourcevalue, array $record = []): int {
        global $DB;

        $userid = $this->getDataGenerator()->create_user($record)->id;
        $DB->insert_record('user_info_data', (object)[
            'userid' => $userid, 'fieldid' => $this->srcid, 'data' => $sourcevalue, 'dataformat' => 0,
        ]);

        return $userid;
    }

    /**
     * The stored value of the target custom profile field, or null when absent.
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
     * Run the task, discarding its mtrace output.
     */
    private function run_task(): void {
        ob_start();
        (new apply_mappings())->execute();
        ob_end_clean();
    }

    /**
     * The task inserts the target row for users who do not have one.
     */
    public function test_creates_missing_target_row(): void {
        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');
        $userid = $this->create_user_with_source('staff');

        $this->run_task();

        $this->assertSame('STAFF', $this->target_value($userid));
    }

    /**
     * Matching ignores case, so the task agrees with the observer.
     *
     * The task used to build a bare LIKE, which is case sensitive on Postgres,
     * while the observer falls back to strcasecmp(). A mixed-case mapping
     * therefore applied in real time but never during the nightly run.
     */
    public function test_matching_is_case_insensitive(): void {
        $this->add_mapping('profile_field_pfasrc', '*@Example.COM', 'profile_field_pfatgt', 'HIT');
        $userid = $this->create_user_with_source('bob@example.com');

        $this->run_task();

        $this->assertSame('HIT', $this->target_value($userid));
    }

    /**
     * An underscore in a mapping is literal text, not a single-character
     * wildcard. The pattern used to be passed to LIKE unescaped, so SQL treated
     * '_' as a wildcard and the mapping matched users it should not have.
     */
    public function test_underscore_is_literal_not_a_wildcard(): void {
        $this->add_mapping('profile_field_pfasrc', 'EMP_1', 'profile_field_pfatgt', 'HIT');

        $exact = $this->create_user_with_source('EMP_1');
        $wildcarded = $this->create_user_with_source('EMP01');

        $this->run_task();

        $this->assertSame('HIT', $this->target_value($exact));
        $this->assertNull(
            $this->target_value($wildcarded),
            'an underscore in the mapping must not act as a single-character wildcard'
        );
    }

    /**
     * A percent sign in a mapping is likewise literal.
     */
    public function test_percent_is_literal_not_a_wildcard(): void {
        $this->add_mapping('profile_field_pfasrc', '100%', 'profile_field_pfatgt', 'HIT');

        $exact = $this->create_user_with_source('100%');
        $wildcarded = $this->create_user_with_source('100 percent complete');

        $this->run_task();

        $this->assertSame('HIT', $this->target_value($exact));
        $this->assertNull(
            $this->target_value($wildcarded),
            'a percent sign in the mapping must not act as a wildcard'
        );
    }

    /**
     * An asterisk still behaves as the wildcard.
     */
    public function test_asterisk_is_the_wildcard(): void {
        $this->add_mapping('profile_field_pfasrc', 'EMP*', 'profile_field_pfatgt', 'HIT');

        $match = $this->create_user_with_source('EMP12345');
        $nomatch = $this->create_user_with_source('CON12345');

        $this->run_task();

        $this->assertSame('HIT', $this->target_value($match));
        $this->assertNull($this->target_value($nomatch));
    }

    /**
     * Users already holding the target value are left alone rather than rewritten.
     */
    public function test_skips_users_already_correct(): void {
        global $DB;

        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');
        $userid = $this->create_user_with_source('staff');
        $DB->insert_record('user_info_data', (object)[
            'userid' => $userid, 'fieldid' => $this->tgtid, 'data' => 'STAFF', 'dataformat' => 0,
        ]);

        $this->run_task();

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

        $this->run_task();

        $this->assertSame('Head Office', $DB->get_field('user', 'institution', ['id' => $userid]));
    }

    /**
     * A standard user field can drive a mapping as the source.
     */
    public function test_standard_user_field_source(): void {
        $this->add_mapping('email', '*@example.com', 'profile_field_pfatgt', 'INTERNAL');
        $userid = $this->getDataGenerator()->create_user(['email' => 'someone@example.com'])->id;

        $this->run_task();

        $this->assertSame('INTERNAL', $this->target_value($userid));
    }

    /**
     * A standard source field outside the allow-list never reaches the query.
     *
     * The mapping is inserted straight into the table, bypassing the validation
     * the form and the CSV importer apply, because the guard exists precisely to
     * hold when a row arrives by some other route. The source field becomes a
     * column identifier, which cannot be bound as a parameter.
     *
     * @dataProvider rejected_source_provider
     * @param string $sourcefield Field name that must not reach the query
     */
    public function test_source_outside_allowlist_is_skipped(string $sourcefield): void {
        $this->add_mapping($sourcefield, '*', 'profile_field_pfatgt', 'HIT');
        $userid = $this->create_user_with_source('anything');

        $this->run_task();

        $this->assertNull($this->target_value($userid));
    }

    /**
     * Source fields that must never be used as a column name.
     *
     * @return array
     */
    public static function rejected_source_provider(): array {
        return [
            'not a column' => ['not_a_column'],
            'password' => ['password'],
            'quote injection' => ["email' OR '1'='1"],
            'comment injection' => ['email -- '],
            'stacked statement' => ['id; DROP TABLE mdl_user'],
        ];
    }

    /**
     * A standard target field outside the updatable allow-list is refused, so the
     * name never becomes a column in the update.
     */
    public function test_target_outside_allowlist_is_refused(): void {
        global $DB;

        $this->add_mapping('profile_field_pfasrc', 'staff', 'username', 'hijacked');
        $userid = $this->create_user_with_source('staff');
        $before = $DB->get_field('user', 'username', ['id' => $userid]);

        $this->run_task();

        $this->assertSame($before, $DB->get_field('user', 'username', ['id' => $userid]));
    }

    /**
     * Suspended and deleted users are outside the task's scope. This is a
     * deliberate difference from the observer, which has no such filter, and is
     * pinned here so the divergence is a decision rather than a surprise.
     */
    public function test_skips_suspended_and_deleted_users(): void {
        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');

        $active = $this->create_user_with_source('staff');
        $suspended = $this->create_user_with_source('staff', ['suspended' => 1]);
        $deleted = $this->create_user_with_source('staff', ['deleted' => 1]);

        $this->run_task();

        $this->assertSame('STAFF', $this->target_value($active));
        $this->assertNull($this->target_value($suspended));
        $this->assertNull($this->target_value($deleted));
    }

    /**
     * A disabled mapping is ignored.
     */
    public function test_disabled_mapping_is_ignored(): void {
        global $DB;

        $this->add_mapping('profile_field_pfasrc', 'staff', 'profile_field_pfatgt', 'STAFF');
        $DB->set_field('local_profilefield_autofill_mapping', 'enabled', 0, []);
        $userid = $this->create_user_with_source('staff');

        $this->run_task();

        $this->assertNull($this->target_value($userid));
    }
}
