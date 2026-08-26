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

namespace core_admin;

/**
 * Unit tests for \core_admin_renderer.
 *
 * @package    core_admin
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \core_admin_renderer
 */
final class renderer_test extends \advanced_testcase {
    /**
     * Get an instance of the admin renderer.
     *
     * @return \core_admin_renderer
     */
    private function get_renderer(): \core_admin_renderer {
        global $PAGE;

        return $PAGE->get_renderer('core', 'admin');
    }

    /**
     * Register the site locally so \core\hub\registration::is_registered() returns true.
     */
    private function register_site(): void {
        global $DB;

        $DB->insert_record('registration_hubs', [
            'token' => 'abc123',
            'hubname' => 'Moodle.org',
            'huburl' => HUB_MOODLEORGHUBURL,
            'confirmed' => 1,
            'secret' => 'secret123',
            'timemodified' => time(),
        ]);
        set_config('site_regupdateversion', max(array_keys(\core\hub\registration::CONFIRM_NEW_FIELDS)), 'hub');
    }

    /**
     * A registered site that is reporting normally shows no registration warning.
     */
    public function test_warn_if_not_registered_reporting_normally(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->register_site();

        $this->assertSame('', $this->get_renderer()->warn_if_not_registered());
    }

    /**
     * A registered site with unconfirmed new registration fields shows a persistent warning.
     */
    public function test_warn_if_not_registered_new_fields_pending(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->register_site();
        set_config('site_regupdateversion', 0, 'hub');

        $warning = $this->get_renderer()->warn_if_not_registered();
        $this->assertStringContainsString(get_string('registrationreportingpausednewfields', 'admin'), $warning);
        $this->assertStringNotContainsString('alert-danger', $warning);
    }

    /**
     * A registered site with the registration cron task disabled shows a persistent warning.
     */
    public function test_warn_if_not_registered_task_disabled(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->register_site();

        $task = \core\task\manager::get_scheduled_task(\core\task\registration_cron_task::class);
        $task->set_disabled(true);
        \core\task\manager::configure_scheduled_task($task);

        $warning = $this->get_renderer()->warn_if_not_registered();
        $this->assertStringContainsString(get_string('registrationreportingpausedtaskdisabled', 'admin'), $warning);
        $this->assertStringNotContainsString('alert-danger', $warning);
    }

    /**
     * When new-fields warnings are suppressed, a registered site with unconfirmed new fields shows nothing.
     *
     * Mirrors public/admin/index.php and public/admin/search.php, which both call
     * \core\hub\registration::registration_reminder() first and would already have redirected the admin
     * away for this exact cause, so this warning must not render there.
     */
    public function test_warn_if_not_registered_new_fields_pending_suppressed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->register_site();
        set_config('site_regupdateversion', 0, 'hub');

        $this->assertSame('', $this->get_renderer()->warn_if_not_registered(false));
    }

    /**
     * Suppressing the new-fields warning must not affect the unrelated task-disabled warning.
     */
    public function test_warn_if_not_registered_task_disabled_not_suppressed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->register_site();

        $task = \core\task\manager::get_scheduled_task(\core\task\registration_cron_task::class);
        $task->set_disabled(true);
        \core\task\manager::configure_scheduled_task($task);

        $warning = $this->get_renderer()->warn_if_not_registered(false);
        $this->assertStringContainsString(get_string('registrationreportingpausedtaskdisabled', 'admin'), $warning);
    }

    /**
     * An unregistered public site still shows the original registration warning.
     */
    public function test_warn_if_not_registered_unregistered(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->site_is_public = true;

        $warning = $this->get_renderer()->warn_if_not_registered();
        $this->assertStringContainsString(get_string('registrationwarning', 'admin'), $warning);
    }
}
