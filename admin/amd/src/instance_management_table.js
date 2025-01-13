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

import PluginManagementTable from 'core_admin/plugin_management_table';
import {call as fetchMany} from 'core/ajax';
import {refreshTableContent} from 'core_table/dynamic';
import Pending from 'core/pending';
import {fetchNotifications} from 'core/notification';
import DeleteCancelModal from 'core/modal_delete_cancel';
import ModalEvents from 'core/modal_events';

/**
 * Handles setting plugin state for the instance management table.
 *
 * @module     core_admin/instance_management_table
 * @copyright  2024 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class extends PluginManagementTable {
    constructor() {
        super();
        this.addClickHandler(this.handleDelete);
    }

    /**
     * Call the delete service.
     *
     * @param {string} methodname The web service to call
     * @param {number} providerid The provider id.
     * @return {Promise} The promise.
     */
    deleteInstance(methodname, providerid) {
        return fetchMany([{
            methodname,
            args: {
                providerid,
            },
        }])[0];
    }

    /**
     * Handle deletion of the instance.
     *
     * @param {HTMLElement} tableRoot
     * @param {Event} e
     */
    async handleDelete(tableRoot, e) {
        const deleteElement = e.target.closest('[data-action="deleteinstance"][data-delete-method]');
        if (deleteElement) {
            e.preventDefault();
            const providerId = e.target.dataset.id;
            const deleteMethod = e.target.dataset.deleteMethod;

            const modal = await DeleteCancelModal.create({
                title: e.target.dataset.title,
                body: e.target.dataset.confirm,
                show: true,
                removeOnClose: true,
            });

            // Handle delete event.
            modal.getRoot().on(ModalEvents.delete, async(e) => {
                e.preventDefault();
                const pendingPromise = new Pending('core_table/dynamic:deleteInstance');
                await this.deleteInstance(deleteMethod, providerId);
                // Reload the table, so we get the updated list of providers, and any messages.
                await Promise.all([
                    refreshTableContent(tableRoot),
                    fetchNotifications(),
                ]);
                modal.destroy();
                pendingPromise.resolve();
            });
        }
    }
}
