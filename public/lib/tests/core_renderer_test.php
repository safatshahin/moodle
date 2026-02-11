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

namespace core;

use core_renderer;
use moodle_page;

/**
 * Tests for \core_renderer.
 *
 * @package   core
 * @category  test
 * @copyright 2024 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \core_renderer
 */
final class core_renderer_test extends \advanced_testcase {
    /**
     * @covers \core\hook\output\before_standard_top_of_body_html_generation
     */
    public function test_standard_top_of_body_html(): void {
        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->standard_top_of_body_html();
        $this->assertIsString($html);
        $this->assertStringNotContainsString('A heading can be added to the top of the body HTML', $html);
    }

    /**
     * @covers \core\hook\output\before_standard_top_of_body_html_generation
     */
    public function test_before_standard_top_of_body_html_generation_hooked(): void {
        require_once(__DIR__ . '/fixtures/core_renderer/before_standard_top_of_body_html_generation_callbacks.php');

        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/core_renderer/before_standard_top_of_body_html_generation_hooks.php',
            ]),
        );

        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->standard_top_of_body_html();
        $this->assertIsString($html);
        $this->assertStringContainsString('A heading can be added to the top of the body HTML', $html);
    }

    /**
     * @covers \core\hook\output\before_footer_html_generation
     */
    public function test_before_footer_html_generation(): void {
        $this->resetAfterTest();
        $page = new moodle_page();
        $page->set_state(moodle_page::STATE_PRINTING_HEADER);
        $page->set_state(moodle_page::STATE_IN_BODY);
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $page->opencontainers->push('header/footer', '</body></html>');
        $html = $renderer->footer();
        $this->assertIsString($html);
        $this->assertStringNotContainsString('A heading can be added', $html);
    }

    /**
     * @covers \core\hook\output\before_footer_html_generation
     */
    public function test_before_footer_html_generation_hooked(): void {
        $this->resetAfterTest();
        require_once(__DIR__ . '/fixtures/core_renderer/before_footer_html_generation_callbacks.php');

        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/core_renderer/before_footer_html_generation_hooks.php',
            ]),
        );

        $page = new moodle_page();
        $page->set_state(moodle_page::STATE_PRINTING_HEADER);
        $page->set_state(moodle_page::STATE_IN_BODY);
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $page->opencontainers->push('header/footer', '</body></html>');
        $html = $renderer->footer();
        $this->assertIsString($html);
        $this->assertStringContainsString('A heading can be added', $html);
    }

    /**
     * @covers \core\hook\output\before_standard_footer_html_generation
     */
    public function before_standard_footer_html_generation(): void {
        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->standard_footer_html();
        $this->assertIsString($html);
        $this->assertStringNotContainsString('A heading can be added', $html);
    }

    /**
     * @covers \core\hook\output\before_standard_footer_html_generation
     */
    public function test_before_standard_footer_html_generation_hooked(): void {
        require_once(__DIR__ . '/fixtures/core_renderer/before_standard_footer_html_generation_callbacks.php');

        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/core_renderer/before_standard_footer_html_generation_hooks.php',
            ]),
        );

        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->standard_footer_html();
        $this->assertIsString($html);
        $this->assertStringContainsString('A heading can be added', $html);
    }

    /**
     * @covers \core\hook\output\after_standard_main_region_html_generation
     */
    public function test_after_standard_main_region_html_generation(): void {
        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->standard_after_main_region_html();
        $this->assertIsString($html);
        $this->assertStringNotContainsString('A heading can be added', $html);
    }

    /**
     * @covers \core\hook\output\after_standard_main_region_html_generation
     */
    public function test_after_standard_main_region_html_generation_hooked(): void {
        require_once(__DIR__ . '/fixtures/core_renderer/after_standard_main_region_html_generation_callbacks.php');

        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/core_renderer/after_standard_main_region_html_generation_hooks.php',
            ]),
        );

        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->standard_after_main_region_html();
        $this->assertIsString($html);
        $this->assertStringContainsString('A heading can be added', $html);
    }

    /**
     * @covers \core\hook\output\before_html_attributes
     */
    public function test_htmlattributes(): void {
        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $attributes = $renderer->htmlattributes();
        $this->assertIsString($attributes);
        $this->assertStringNotContainsString('data-test="test"', $attributes);
    }

    /**
     * @covers \core\hook\output\before_html_attributes
     */
    public function test_htmlattributes_hooked(): void {
        require_once(__DIR__ . '/fixtures/core_renderer/htmlattributes_callbacks.php');

        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'test_plugin1' => __DIR__ . '/fixtures/core_renderer/htmlattributes_hooks.php',
            ]),
        );

        $page = new moodle_page();
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $attributes = $renderer->htmlattributes();
        $this->assertIsString($attributes);
        $this->assertStringContainsString('data-test="test"', $attributes);
    }

    /**
     * @covers \core_renderer::course_content_header
     */
    public function test_course_content_header_shows_course_progress_for_enrolled_user(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id], ['completion' => COMPLETION_TRACKING_MANUAL]);

        $this->setUser($user);

        $page = new moodle_page();
        $page->set_course($course);
        $page->set_context(\context_course::instance($course->id));
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->course_content_header();
        $this->assertStringContainsString('data-region="course-progress"', $html);
        $this->assertStringContainsString('0%', $html);
    }

    /**
     * @covers \core_renderer::course_content_header
     */
    public function test_course_content_header_hides_course_progress_when_completion_is_disabled(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 0]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $this->setUser($user);

        $page = new moodle_page();
        $page->set_course($course);
        $page->set_context(\context_course::instance($course->id));
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->course_content_header();
        $this->assertStringNotContainsString('data-region="course-progress"', $html);
    }

    /**
     * @covers \core_renderer::course_content_header
     */
    public function test_course_content_header_hides_course_progress_for_not_enrolled_user(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id], ['completion' => COMPLETION_TRACKING_MANUAL]);

        $this->setUser($user);

        $page = new moodle_page();
        $page->set_course($course);
        $page->set_context(\context_course::instance($course->id));
        $renderer = new core_renderer($page, RENDERER_TARGET_GENERAL);

        $html = $renderer->course_content_header();
        $this->assertStringNotContainsString('data-region="course-progress"', $html);
    }
}
