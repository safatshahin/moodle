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
 * Expand the collapse section element based on hash URL (e.g. #collapseElement-1)
 *
 * @module      core_admin/expand_hash
 * @copyright   Meirza <meirza.arson@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       4.5
 */

const SELECTORS = {
    COLLAPSE_ELEMENTS: '[data-toggle="collapse"]',
};

/**
 * Initialises the hash expand collapse.
 */
export const init = () => {
    // Select all collapsible elements only.
    document.querySelectorAll(SELECTORS.COLLAPSE_ELEMENTS).forEach(element => {
        element.addEventListener('focus', event => {
            if (window.location.hash === `#${event.target.id}`) {
                expandSection(window.location.hash);
            }
        }, true);
    });
};

/**
 * Expands the section based on the current URL hash.
 *
 * This function checks if there is a hash in the current URL. If there is,
 * it selects the corresponding element and triggers a click event on it
 * to expand the section.
 *
 * @param {string} hash - The hash (e.g. `#collapseElement-1`) of the element to expand.
 */
export const expandSection = (hash) => {
    const targetContainer = document.querySelector(hash);
    if (
        targetContainer?.getAttribute('data-toggle') === 'collapse' &&
        targetContainer?.getAttribute('aria-expanded') === 'false'
    ) {
        const collapseId = targetContainer.getAttribute('aria-controls');
        const collapseContainer = document.getElementById(collapseId);

        // Remove 'collapse' class and add 'show' class to show the content.
        collapseContainer.classList.remove('collapse');
        collapseContainer.classList.add('show');

        // Update aria-expanded attribute to reflect the new state.
        targetContainer.setAttribute('aria-expanded', 'true');
    }
};
