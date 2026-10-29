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

namespace core\db;

use context_system;
use context_user;

/**
 * Tests for the migration of dashboard blocks to the dashboard grid.
 *
 * The migration is one-way: there is no legacy dashboard to fall back to, so these cover every
 * branch of it - the site default, customised user dashboards, pre-existing block_positions
 * rows (updated in place rather than duplicated), relative order within each legacy region,
 * blocks without a sub page or with a wildcard page type, more pages than one chunk of the
 * paging loop, and pages whose user no longer exists.
 *
 * @package    core
 * @category   test
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('upgrade_migrate_dashboard_blocks_to_grid')]
final class upgrade_dashboard_grid_test extends \advanced_testcase {
    /** The rows the first block of a legacy stack is given. */
    private const ROWS_FIRST = 5;

    /** The rows every other block of a legacy stack is given. */
    private const ROWS = 3;

    /**
     * Run the migration.
     *
     * @param int $chunksize How many dashboard pages the migration reads at a time.
     */
    private function run_migration(int $chunksize = 500): void {
        global $CFG;

        require_once("{$CFG->libdir}/upgradelib.php");
        require_once("{$CFG->libdir}/db/upgradelib.php");
        upgrade_migrate_dashboard_blocks_to_grid($chunksize);
    }

    /**
     * Remove the install-time default blocks from the site default dashboard page, so that the
     * blocks a test creates are the only ones on it.
     *
     * @return \stdClass The site default dashboard page record.
     */
    private function clear_site_default_page(): \stdClass {
        global $DB;

        $page = $DB->get_record('my_pages', [
            'userid' => null,
            'name' => '__default',
            'private' => 1,
        ], '*', MUST_EXIST);
        foreach ($DB->get_records('block_instances', $this->instance_criteria(context_system::instance(), $page)) as $old) {
            blocks_delete_instance($old);
        }
        return $page;
    }

    /**
     * The block_instances fields which attach an instance to a dashboard page.
     *
     * @param \context $context The page's context.
     * @param \stdClass $page The my_pages record.
     * @return array
     */
    private function instance_criteria(\context $context, \stdClass $page): array {
        return [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'my-index',
            'subpagepattern' => (string) $page->id,
        ];
    }

    /**
     * Legacy main and side-region stacks on the site default are migrated to the six-column grid.
     */
    public function test_legacy_regions_are_migrated(): void {
        global $DB;

        $this->resetAfterTest();
        $page = $this->clear_site_default_page();
        $common = $this->instance_criteria(context_system::instance(), $page);
        $main = $this->getDataGenerator()->create_block('html', $common + [
            'defaultregion' => 'content',
            'defaultweight' => 50,
        ]);
        $side = $this->getDataGenerator()->create_block('online_users', $common + [
            'defaultregion' => 'side-pre',
            'defaultweight' => 50,
        ]);
        $postside = $this->getDataGenerator()->create_block('calendar_month', $common + [
            'defaultregion' => 'side-post',
            'defaultweight' => 50,
        ]);

        $this->run_migration();

        $mainposition = $DB->get_record('block_positions', ['blockinstanceid' => $main->id], '*', MUST_EXIST);
        $sideposition = $DB->get_record('block_positions', ['blockinstanceid' => $side->id], '*', MUST_EXIST);
        $postsideposition = $DB->get_record('block_positions', ['blockinstanceid' => $postside->id], '*', MUST_EXIST);
        $this->assertSame(1, (int) $mainposition->gridcolumn);
        $this->assertSame(3, (int) $mainposition->gridcolumns);
        $this->assertSame(4, (int) $sideposition->gridcolumn);
        $this->assertSame(1, (int) $sideposition->gridcolumns);
        $this->assertSame(4, (int) $postsideposition->gridcolumn);
        $this->assertSame(1, (int) $postsideposition->gridcolumns);
        $this->assertTrue(
            (int) $sideposition->gridrow + (int) $sideposition->gridrows <= (int) $postsideposition->gridrow
                || (int) $postsideposition->gridrow + (int) $postsideposition->gridrows <= (int) $sideposition->gridrow,
            'Legacy side-pre and side-post blocks must not overlap after migration.',
        );
        // The first block in each stack is taller.
        $this->assertSame(self::ROWS_FIRST, (int) $mainposition->gridrows);
        $this->assertSame(self::ROWS_FIRST, (int) $sideposition->gridrows);
        $this->assertSame(self::ROWS, (int) $postsideposition->gridrows);
    }

    /**
     * A customised user dashboard is migrated in its own user context, keeps the relative order of
     * blocks within each legacy region (by their saved or default weight), and updates a block's
     * existing block_positions row in place rather than leaving it behind or duplicating it.
     */
    public function test_user_dashboard_is_migrated_preserving_order(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/my/lib.php');
        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);
        $page = my_copy_page($user->id, MY_PAGE_PRIVATE);
        $this->assertNotFalse($page);
        $criteria = $this->instance_criteria($context, $page);
        foreach ($DB->get_records('block_instances', $criteria) as $copied) {
            blocks_delete_instance($copied);
        }

        // Three content blocks whose default weights put them in the order second, first, third;
        // the first of them also has a saved position which moves it into the side region.
        $second = $this->getDataGenerator()->create_block('html', $criteria + [
            'defaultregion' => 'content',
            'defaultweight' => 2,
        ]);
        $first = $this->getDataGenerator()->create_block('online_users', $criteria + [
            'defaultregion' => 'content',
            'defaultweight' => 1,
        ]);
        $third = $this->getDataGenerator()->create_block('calendar_month', $criteria + [
            'defaultregion' => 'content',
            'defaultweight' => 3,
        ]);
        $moved = $this->getDataGenerator()->create_block('comments', $criteria + [
            'defaultregion' => 'content',
            'defaultweight' => 0,
        ]);
        $existingpositionid = $DB->insert_record('block_positions', (object) [
            'blockinstanceid' => $moved->id,
            'contextid' => $context->id,
            'pagetype' => 'my-index',
            'subpage' => (string) $page->id,
            'visible' => 1,
            'region' => 'side-pre',
            'weight' => 7,
        ]);

        $this->run_migration();

        $positions = [];
        foreach ([$first, $second, $third, $moved] as $block) {
            $rows = $DB->get_records('block_positions', ['blockinstanceid' => $block->id]);
            $this->assertCount(1, $rows, "Block {$block->blockname} must have exactly one position row.");
            $positions[$block->id] = reset($rows);
            $this->assertSame((int) $context->id, (int) $positions[$block->id]->contextid);
        }

        // Content blocks stack in weight order down the content column.
        $this->assertSame(1, (int) $positions[$first->id]->gridcolumn);
        $this->assertSame(0, (int) $positions[$first->id]->gridrow);
        $this->assertSame(self::ROWS_FIRST, (int) $positions[$first->id]->gridrows);
        $this->assertSame(1, (int) $positions[$second->id]->gridcolumn);
        $this->assertSame(self::ROWS_FIRST, (int) $positions[$second->id]->gridrow);
        $this->assertSame(self::ROWS, (int) $positions[$second->id]->gridrows);
        $this->assertSame(1, (int) $positions[$third->id]->gridcolumn);
        $this->assertSame(self::ROWS_FIRST + self::ROWS, (int) $positions[$third->id]->gridrow);

        // The block with a saved side-region position goes to the drawer column, through an
        // update of its existing row.
        $this->assertSame((int) $existingpositionid, (int) $positions[$moved->id]->id);
        $this->assertSame(4, (int) $positions[$moved->id]->gridcolumn);
        $this->assertSame(0, (int) $positions[$moved->id]->gridrow);
        $this->assertSame('side-pre', $positions[$moved->id]->region);
        $this->assertSame(7, (int) $positions[$moved->id]->weight);
    }

    /**
     * A block the user has configured on their own dashboard has no sub page any more
     * (block_manager::save_block_data() clears it in a user context), yet still belongs to that
     * dashboard: it is migrated with the rest, in its place in the stack, not left behind to be
     * laid over a migrated block at runtime.
     */
    public function test_configured_block_without_subpage_is_migrated(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/my/lib.php');
        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);
        $page = my_copy_page($user->id, MY_PAGE_PRIVATE);
        $this->assertNotFalse($page);
        $criteria = $this->instance_criteria($context, $page);
        foreach ($DB->get_records('block_instances', $criteria) as $copied) {
            blocks_delete_instance($copied);
        }

        $configured = $this->getDataGenerator()->create_block('html', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'my-index',
            'subpagepattern' => null,
            'defaultregion' => 'content',
            'defaultweight' => 0,
        ]);
        $this->assertNull($DB->get_field('block_instances', 'subpagepattern', ['id' => $configured->id]));
        // The configured block also has a saved position on the page, as one moved in the
        // legacy editor would.
        $existingpositionid = $DB->insert_record('block_positions', (object) [
            'blockinstanceid' => $configured->id,
            'contextid' => $context->id,
            'pagetype' => 'my-index',
            'subpage' => (string) $page->id,
            'visible' => 1,
            'region' => 'content',
            'weight' => 0,
        ]);
        $other = $this->getDataGenerator()->create_block('online_users', $criteria + [
            'defaultregion' => 'content',
            'defaultweight' => 1,
        ]);

        $this->run_migration();

        $configuredposition = $DB->get_record('block_positions', ['blockinstanceid' => $configured->id], '*', MUST_EXIST);
        $otherposition = $DB->get_record('block_positions', ['blockinstanceid' => $other->id], '*', MUST_EXIST);
        $this->assertSame((int) $existingpositionid, (int) $configuredposition->id);
        $this->assertSame((string) $page->id, $configuredposition->subpage);
        $this->assertSame(1, (int) $configuredposition->gridcolumn);
        $this->assertSame(0, (int) $configuredposition->gridrow);
        $this->assertSame(self::ROWS_FIRST, (int) $configuredposition->gridrows);
        // The block after it stacks below, rather than sharing its cells.
        $this->assertSame(1, (int) $otherposition->gridcolumn);
        $this->assertSame(self::ROWS_FIRST, (int) $otherposition->gridrow);
    }

    /**
     * A block whose page type pattern only matches the dashboard through a wildcard is on the
     * dashboard all the same, and is migrated with the blocks which name the page type exactly.
     */
    public function test_blocks_with_wildcard_page_types_are_migrated(): void {
        global $DB;

        $this->resetAfterTest();
        $page = $this->clear_site_default_page();
        $context = context_system::instance();
        $exact = $this->getDataGenerator()->create_block('html', $this->instance_criteria($context, $page) + [
            'defaultregion' => 'content',
            'defaultweight' => 0,
        ]);
        $wildcard = $this->getDataGenerator()->create_block('online_users', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'my-*',
            'subpagepattern' => (string) $page->id,
            'defaultregion' => 'content',
            'defaultweight' => 1,
        ]);
        // A block of another page type in the same context is not on the dashboard.
        $elsewhere = $this->getDataGenerator()->create_block('calendar_month', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'site-index',
            'subpagepattern' => null,
            'defaultregion' => 'content',
            'defaultweight' => 2,
        ]);

        $this->run_migration();

        $exactposition = $DB->get_record('block_positions', ['blockinstanceid' => $exact->id], '*', MUST_EXIST);
        $wildcardposition = $DB->get_record('block_positions', ['blockinstanceid' => $wildcard->id], '*', MUST_EXIST);
        $this->assertSame(0, (int) $exactposition->gridrow);
        $this->assertSame(1, (int) $wildcardposition->gridcolumn);
        $this->assertSame(self::ROWS_FIRST, (int) $wildcardposition->gridrow);
        $this->assertSame((string) $page->id, $wildcardposition->subpage);
        $this->assertFalse($DB->record_exists('block_positions', ['blockinstanceid' => $elsewhere->id]));
    }

    /**
     * A block already holding a grid position is left exactly as it is: the migration is safe to
     * run again, and never overwrites an arrangement the user has made on the grid.
     */
    public function test_migration_leaves_existing_grid_positions_alone(): void {
        global $DB;

        $this->resetAfterTest();
        $page = $this->clear_site_default_page();
        $common = $this->instance_criteria(context_system::instance(), $page);
        $arranged = $this->getDataGenerator()->create_block('html', $common + [
            'defaultregion' => 'content',
            'defaultweight' => 1,
        ]);
        $unmigrated = $this->getDataGenerator()->create_block('online_users', $common + [
            'defaultregion' => 'content',
            'defaultweight' => 2,
        ]);

        $positionid = $DB->insert_record('block_positions', (object) [
            'blockinstanceid' => $arranged->id,
            'contextid' => context_system::instance()->id,
            'pagetype' => 'my-index',
            'subpage' => (string) $page->id,
            'visible' => 1,
            'region' => 'content',
            'weight' => 1,
            'gridcolumn' => 5,
            'gridrow' => 7,
            'gridcolumns' => 1,
            'gridrows' => 9,
        ]);

        $this->run_migration();

        $kept = $DB->get_record('block_positions', ['blockinstanceid' => $arranged->id], '*', MUST_EXIST);
        $this->assertSame((int) $positionid, (int) $kept->id);
        $this->assertSame([5, 7, 1, 9], [
            (int) $kept->gridcolumn, (int) $kept->gridrow, (int) $kept->gridcolumns, (int) $kept->gridrows,
        ]);
        $migrated = $DB->get_record('block_positions', ['blockinstanceid' => $unmigrated->id], '*', MUST_EXIST);
        $this->assertSame(1, (int) $migrated->gridcolumn);
        $this->assertSame(0, (int) $migrated->gridrow);
    }

    /**
     * The migration pages through my_pages in chunks; a site with more customised dashboards
     * than one chunk holds must still migrate every one of them.
     */
    public function test_migration_covers_more_pages_than_one_chunk(): void {
        global $DB;

        $this->resetAfterTest();
        $this->clear_site_default_page();

        // Enough user dashboards to need a third chunk at two pages a chunk.
        // Records are inserted directly, as the migration itself only reads tables: the users,
        // their pages and one block each.
        $count = 5;
        $blockids = [];
        for ($i = 0; $i < $count; $i++) {
            $userid = $DB->insert_record('user', (object) [
                'username' => "dashboarduser{$i}",
                'email' => "dashboarduser{$i}@example.com",
                'firstname' => 'Dashboard',
                'lastname' => (string) $i,
                'confirmed' => 1,
                'mnethostid' => 1,
            ]);
            $context = context_user::instance($userid);
            $pageid = $DB->insert_record('my_pages', (object) [
                'userid' => $userid,
                'name' => '__default',
                'private' => 1,
                'sortorder' => 0,
            ]);
            $blockids[] = $DB->insert_record('block_instances', (object) [
                'blockname' => 'html',
                'parentcontextid' => $context->id,
                'showinsubcontexts' => 0,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => (string) $pageid,
                'defaultregion' => 'content',
                'defaultweight' => 0,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $this->run_migration(2);

        [$insql, $inparams] = $DB->get_in_or_equal($blockids);
        $this->assertSame(
            $count,
            $DB->count_records_select('block_positions', "blockinstanceid {$insql} AND gridcolumn IS NOT NULL", $inparams),
        );
    }

    /**
     * A dashboard page left behind by a user who no longer exists is skipped without error, and
     * the pages after it are still migrated.
     */
    public function test_migration_skips_pages_of_missing_users(): void {
        global $DB;

        $this->resetAfterTest();
        $this->clear_site_default_page();
        $DB->insert_record('my_pages', (object) [
            'userid' => 987654321,
            'name' => '__default',
            'private' => 1,
            'sortorder' => 0,
        ]);
        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);
        $pageid = $DB->insert_record('my_pages', (object) [
            'userid' => $user->id,
            'name' => '__default',
            'private' => 1,
            'sortorder' => 0,
        ]);
        $block = $this->getDataGenerator()->create_block('html', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'my-index',
            'subpagepattern' => (string) $pageid,
            'defaultregion' => 'content',
        ]);

        $this->run_migration();

        $position = $DB->get_record('block_positions', ['blockinstanceid' => $block->id], '*', MUST_EXIST);
        $this->assertSame(1, (int) $position->gridcolumn);
    }
}
