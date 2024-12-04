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

import BaseClass from 'core_admin/plugin_management_table';
import * as DynamicTable from 'core_table/dynamic';
import Notification from 'core/notification';
import Fragment from 'core/fragment';

const Selectors = {
    mfaCombinationsWrapper: '#mfacombinations-wrapper',
};

export default class extends BaseClass {
    registerEventListeners() {
        super.registerEventListeners();

        document.addEventListener(DynamicTable.Events.tableContentRefreshed, () => {
            Fragment.loadFragment('tool_mfa', 'factor_combinations', M.cfg.contextid, [])
            .then((html) => {
                const factorCombinations = document.querySelector(Selectors.mfaCombinationsWrapper);
                factorCombinations.innerHTML = html;
                return;
            })
            .catch(Notification.exception);
        });
    }
}
