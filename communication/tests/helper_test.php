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

namespace core_communication;

use communication_matrix\matrix_test_helper_trait;
use core_communication\processor as communication_processor;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../provider/matrix/tests/matrix_test_helper_trait.php');
require_once(__DIR__ . '/communication_test_helper_trait.php');



/**
 * Test communication helper methods.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_communication\helper
 */
class helper_test extends \advanced_testcase {
    use communication_test_helper_trait;
    use matrix_test_helper_trait;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setup_communication_configs();
        $this->initialise_mock_server();
    }

    /**
     * Test load_by_group.
     *
     * @covers ::load_by_group
     */
    public function test_load_by_group(): void {

        // As communication is created by default.
        $course = $this->get_course(
            extrafields: ['groupmode' => SEPARATEGROUPS],
        );
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $context = \context_course::instance(courseid: $course->id);

        $groupcommunication = helper::load_by_group(
            groupid: $group->id,
            context: $context,
        );
        $this->assertInstanceOf(
            expected: communication_processor::class,
            actual: $groupcommunication->get_processor(),
        );
    }
}
