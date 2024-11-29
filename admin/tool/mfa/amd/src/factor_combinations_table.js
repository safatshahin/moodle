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
 * Get the factor combinations table.
 *
 * @module     tool_mfa/factor_combinations_table
 * @copyright  Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as DynamicTable from 'core_table/dynamic';
import Notification from 'core/notification';
import Ajax from 'core/ajax';

const Selectors = {
    mfaCombinationsWrapper: '#mfacombinations-wrapper',
    tableForm: uniqueId => `[data-region="core_table/dynamic"][data-table-uniqueid="${uniqueId}"]`,
};

export default class {
    /**
     * Creates an instance of the class, sets the root and target elements, and registers event listeners.
     *
     * @param {string} uniqueId - The unique identifier for the dynamic table form.
     */
    constructor(uniqueId) {
        this.mfaManagement = document.querySelector(Selectors.tableForm(uniqueId));
        this.factorCombinations = document.querySelector(Selectors.mfaCombinationsWrapper);
        this.registerEventListeners();
    }

    /**
     * Initialise an instance of the class.
     *
     * @param {string} uniqueId - The unique identifier for the dynamic table form.
     */
    static init(uniqueId) {
        new this(uniqueId);
    }

    /**
     * Registers event listeners for the table content refresh event.
     * Updates the content of the MFA combinations table when the event is triggered.
     */
    registerEventListeners() {
        document.addEventListener(DynamicTable.Events.tableContentRefreshed, () => {
            // Only proceed if the MFA management table & factor combinations div wrapper are exist.
            if (this.mfaManagement && this.factorCombinations) {
                this.getFactorCombinationsTable()
                .then((response) => {
                    this.factorCombinations.innerHTML = response.html;
                    return;
                })
                .catch(Notification.exception);
            }
        });
    }

    /**
     * Makes an AJAX request to retrieve the HTML for the MFA combinations table.
     *
     * @returns {Promise<Object>} A promise that resolves with the response containing the table HTML.
     */
    getFactorCombinationsTable() {
        const request = {
            methodname: 'tool_mfa_get_factor_combinations_table',
            args: {},
        };

        return Ajax.call([request])[0];
    }
}
