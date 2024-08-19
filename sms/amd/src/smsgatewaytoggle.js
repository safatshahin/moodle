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

import {refreshTableContent} from 'core_table/dynamic';
import * as Selectors from 'core_table/local/dynamic/selectors';
import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getStrings} from 'core/str';
import {fetchNotifications} from 'core/notification';

let watching = false;

/**
 * SMS gateway status handler.
 *
 * @module     core_sms/smsgatewaytoggle
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class {
    /**
     * @property {function[]} clickHandlers a list of handlers to call on click.
     */
    clickHandlers = [];

    constructor() {
        this.addClickHandler(this.handleStateToggle);
        this.registerEventListeners();
    }

    /**
     * Initialise an instance of the class.
     *
     * This is just a way of making it easier to initialise an instance of the class from PHP.
     */
    static init() {
        if (watching) {
            return;
        }
        watching = true;
        new this();
    }

    /**
     * Add a click handler to the list of handlers.
     *
     * @param {Function} handler A handler to call on a click event
     */
    addClickHandler(handler) {
        this.clickHandlers.push(handler.bind(this));
    }

    /**
     * Register the event listeners for this instance.
     */
    registerEventListeners() {
        document.addEventListener('click', function(e) {
            const tableRoot = this.getTableRoot(e);

            if (!tableRoot) {
                return;
            }

            this.clickHandlers.forEach((handler) => handler(tableRoot, e));
        }.bind(this));
    }

    /**
     * Get the table root from an event.
     *
     * @param {Event} e
     * @returns {HTMLElement|bool}
     */
    getTableRoot(e) {
        const tableRoot = e.target.closest(Selectors.main.region);
        if (!tableRoot) {
            return false;
        }

        return tableRoot;
    }

    /**
     * Set the plugin state (enabled or disabled)
     *
     * @param {string} methodname The web service to call
     * @param {int} gateway The gateway id
     * @param {int} enabled The state to set
     * @returns {Promise}
     */
    setGatewayState(methodname, gateway, enabled) {
        return fetchMany([{
            methodname,
            args: {
                gateway,
                enabled,
            },
        }])[0];
    }

    /**
     * Handle state toggling.
     *
     * @param {HTMLElement} tableRoot
     * @param {Event} e
     */
    async handleStateToggle(tableRoot, e) {
        const stateToggle = e.target.closest('[data-action="togglestate"][data-toggle-method]');
        if (stateToggle) {
            e.preventDefault();
            const pendingPromise = new Pending('core_table/dynamic:togglestate');

            const response = await this.setGatewayState(
                stateToggle.dataset.toggleMethod,
                parseInt(stateToggle.dataset.gatewayid),
                stateToggle.dataset.state === '1' ? 0 : 1
            );

            if (!response.result) {
                getStrings([
                    {key: response.message, component: 'sms'},
                ]).then(([message]) =>
                    // Reset form dirty state on confirmation, re-trigger the event.
                    Notification.addNotification({
                        message: message,
                        type: response.messageType
                    })
                ).catch(Notification.exception);
            }

            const [updatedRoot] = await Promise.all([
                refreshTableContent(tableRoot),
                fetchNotifications(),
            ]);

            // Refocus on the link that as pressed in the first place.
            updatedRoot.querySelector(`[data-action="togglestate"][data-gatewayid="${stateToggle.dataset.gatewayid}"]`).focus();
            pendingPromise.resolve();
        }
    }
}
