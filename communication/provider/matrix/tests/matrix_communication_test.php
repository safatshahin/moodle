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

namespace communication_matrix;

use core_communication\communication_processor;
use core_communication\communication_test_helper_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/matrix_test_helper_trait.php');
require_once(__DIR__ . '/../../../tests/communication_test_helper_trait.php');

/**
 * Class matrix_provider_test to test the matrix provider scenarios using the matrix endpoints.
 *
 * @package    communication_matrix
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_communication\task\add_members_to_room_task
 * @coversDefaultClass \core_communication\task\remove_members_from_room
 * @coversDefaultClass \core_communication\task\create_and_configure_room_task
 * @coversDefaultClass \core_communication\task\remove_members_from_room
 * @coversDefaultClass \core_communication\task\update_room_task
 * @coversDefaultClass \core_communication\communication_processor
 * @coversDefaultClass \core_communication\api
 */
class matrix_communication_test extends \advanced_testcase {

    use matrix_test_helper_trait;
    use communication_test_helper_trait;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->initialise_mock_server();
    }

    /**
     * Test creating course with matrix provider creates all the associated data and matrix room.
     *
     * @covers ::execute
     */
    public function test_create_course_with_matrix_provider(): void {
        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );

        // Initialize the matrix room object.
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());

        // Test against the data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_matrix_room_id());
        $this->assertEquals($matrixrooms->get_matrix_room_id(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_matrix_room_alias(), $matrixroomdata->canonical_alias);
        $this->assertEquals($roomname, $matrixroomdata->name);
    }

    /**
     * Test update course with matrix provider.
     *
     * @covers ::execute
     */
    public function test_update_course_with_matrix_provider(): void {
        global $CFG;
        $course = $this->get_course();

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Sample data.
        $communicationroomname = 'Sampleroomupdated';
        $selectedcommunication = 'communication_matrix';
        $avatarurl = $CFG->dirroot . '/communication/tests/fixtures/moodle_logo.jpg';

        $communication = \core_communication\api::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $communication->update_room($selectedcommunication, $communicationroomname, $avatarurl);

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\update_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );

        // Initialize the matrix room object.
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());

        // Test against the data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_matrix_room_id());
        $this->assertEquals($matrixrooms->get_matrix_room_id(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_matrix_room_alias(), $matrixroomdata->canonical_alias);
        $this->assertEquals($communicationroomname, $matrixroomdata->name);
        $this->assertNotEmpty($matrixroomdata->avatar);
    }

    /**
     * Test course delete with matrix provider.
     *
     * @covers ::execute
     */
    public function test_delete_course_with_matrix_provider(): void {
        global $DB;
        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $communicationid = $communicationprocessor->get_id();

        // Initialize the matrix room object.
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());

        // Test against the data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_matrix_room_id());
        $this->assertEquals($matrixrooms->get_matrix_room_id(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_matrix_room_alias(), $matrixroomdata->canonical_alias);

        // Now delete the course.
        delete_course($course, false);

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\delete_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $this->assertNull($communicationprocessor);

        // Initialize the matrix room object.
        $matrixrooms = $DB->get_record('matrix_rooms', ['commid' => $communicationid]);
        $this->assertEmpty($matrixrooms);
    }

    /**
     * Test creating course with matrix provider creates all the associated data and matrix room.
     *
     * @covers ::execute
     */
    public function test_create_members_with_matrix_provider(): void {
        // Insert required fields first.
        $this->run_post_install_task();

        $course = $this->get_course('Samplematrixroom', 'communication_matrix');
        $user = $this->get_user('Samplefnmatrix', 'Samplelnmatrix', 'sampleunmatrix');

        // Run room operation task.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $enrol->enrol_user(reset($enrolinstances), $user->id);

        // Run user operation task.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());

        // Get matrix user id from moodle.
        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $eventmanager->matrixhomeserverurl);
        $this->assertNotNull($matrixuserid);

        // Get matrix user id from matrix.
        $matrixuserdata = $this->get_matrix_user_data($matrixrooms->get_matrix_room_id(), $matrixuserid);
        $this->assertNotEmpty($matrixuserdata);
        $this->assertEquals("Samplefnmatrix Samplelnmatrix", $matrixuserdata->displayname);
    }

    /**
     * Test enrolment adds the user to a Matrix room.
     *
     * @covers ::execute
     */
    public function test_enrolling_user_adds_user_to_matrix_room(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );

        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolment removes the user from a Matrix room.
     *
     * @covers ::execute
     */
    public function test_unenrolling_user_removes_user_from_matrix_room(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );

        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Unenrol the user from the course.
        $enrol->unenrol_user($instance, $user->id);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolled users in a course lose access to a room when their enrolment is suspended.
     *
     * @covers ::execute
     */
    public function test_users_removed_from_room_when_suspending_enrolment(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Suspend user enrolment.
        $enrol->update_user_enrol($instance, $user->id, 1);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolled users in a course lose access to a room when the instance is deleted.
     *
     * @return void
     * @covers ::remove_members_from_room
     * @covers ::check_room_membership
     * @covers ::remove_members
     */
    public function test_users_removed_from_room_when_deleting_instance(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Delete instance.
        $enrol->delete_instance($instance);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolled users in a course lose access to a room when the instance is disabled.
     *
     * @covers ::execute
     */
    public function test_users_removed_from_room_when_disabling_instance(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionality of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Update enrolment communication.
        $enrol->update_communication($instance->id, 'remove', $course->id);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolled users memerbship toggles correctly when an instance is disabled and reenabled again.
     *
     * @covers ::execute
     */
    public function test_users_memerbship_toggles_when_disabling_and_reenabling_instance(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Update enrolment communication when updating instance to disabled.
        $enrol->update_communication($instance->id, 'remove', $course->id);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Update enrolment communication when updating instance to enabled.
        $enrol->update_communication($instance->id, 'add', $course->id);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');
        // Check our Matrix user id no longer has membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolled users in a course lose access to a room when the provider is disabled.
     *
     * @covers ::execute
     */
    public function test_users_removed_from_room_when_disabling_provider(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/course/lib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Disable communication provider.
        $course->selectedcommunication = 'none';
        update_course($course);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolled users in a course lose access to a room when their user account is suspended.
     *
     * @covers ::execute
     */
    public function test_users_removed_from_room_when_suspending_user(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Suspend user.
        $user->suspended = 1;
        user_update_user($user, false, false);
        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }

    /**
     * Test enrolled users in a course lose access to a room when their user account is deleted.
     *
     * @covers ::execute
     */
    public function test_users_removed_from_room_when_deleting_user(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // Add important fields for functionalty of test.
        $this->run_post_install_task();

        // Sample data.
        $roomname = 'Samplematrixroom';
        $provider = 'communication_matrix';
        $course = $this->get_course($roomname, $provider);
        $user = $this->get_user();

        // Run room tasks.
        $this->runAdhocTasks('\core_communication\task\create_and_configure_room_task');

        // Enrol the user in the course.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = reset($enrolinstances);
        $enrol->enrol_user($instance, $user->id);

        // Run the user tasks.
        $this->runAdhocTasks('\core_communication\task\add_members_to_room_task');

        $communicationprocessor = communication_processor::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id
        );
        $matrixrooms = new matrix_rooms($communicationprocessor->get_id());
        $eventmanager = new matrix_events_manager($matrixrooms->get_matrix_room_id());
        $matrixhomeserverurl = $eventmanager->matrixhomeserverurl;

        $matrixuserid = matrix_user_manager::get_matrixid_from_moodle($user->id, $matrixhomeserverurl);
        // Check our Matrix user id has room membership.
        $this->assertTrue($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
        // Delete user.
        delete_user($user);
        // Run the user tasks.
        // $this->runAdhocTasks('\core_communication\task\remove_members_from_room');
        // Check our Matrix user id no longer has membership.
        $this->assertFalse($communicationprocessor->get_room_provider()->check_room_membership($matrixuserid));
    }
}
