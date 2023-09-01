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

/**
 * Interface for the methods related to provider configuration.
 *
 * Every plugin will have different configuration and different way of acting when its enabled and even when disabled.
 * These methods will help to get the configuration of the plugin and help to check them when needed.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface provider_configuration {

    /**
     * Check if the provider is configured or not.
     *
     * This method is intended to check if the plugin have got any settings and if all the settings are set properly.
     * This checking helps to reduce errors in future when a communication instance is added for the provider and not configured.
     */
    public static function is_configured(): bool;

    /**
     * Check if the provider is enabled or not.
     *
     * This method could live outside of this interface but having this here have some benefits.
     * One of the biggest benefit of this will be when it needs to check for another feature or plugin to be enabled as well.
     */
    public static function is_enabled(): bool;

}
