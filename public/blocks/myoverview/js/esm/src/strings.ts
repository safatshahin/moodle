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
 * Client-side language strings for the course overview block (MDL-89070 review).
 *
 * The UI strings are fetched here via @moodle/lms/core/stringUtils rather than
 * being serialised into the mount props, keeping the props minimal as the
 * frontend docs require. One batched request resolves every string the block
 * needs; the results are cached by core (M.str / localStorage), so this costs at
 * most one round trip per session.
 *
 * @module     block_myoverview/strings
 */

import {getStrings} from "@moodle/lms/core/stringUtils";
import {Strings} from "./types";

const COMPONENT = "block_myoverview";

// Each Strings key mapped to its lang string identifier and component. The values
// mirror the map the PHP layer previously serialised into the props, so the same
// language strings (and translations) are used.
const STRING_MAP: Record<keyof Strings, {key: string; component?: string}> = {
    actionsfor: {key: "aria:courseactionsfor", component: COMPONENT},
    changelayout: {key: "aria:displaydropdown", component: COMPONENT},
    clearsearch: {key: "clear"},
    courseactions: {key: "aria:courseactions", component: COMPONENT},
    courseoverview: {key: "pluginname", component: COMPONENT},
    courseprogress: {key: "courseprogress", component: COMPONENT},
    createcourse: {key: "createcourse", component: COMPONENT},
    emptyallhiddenintro: {key: "allhidden_intro", component: COMPONENT},
    emptyallhiddentitle: {key: "allhidden_title", component: COMPONENT},
    emptyeducator: {key: "zero_default_intro", component: COMPONENT},
    emptynoresults: {key: "noresults_intro", component: COMPONENT},
    emptynoresultstitle: {key: "noresults_title", component: COMPONENT},
    emptystudent: {key: "zero_default_intro", component: COMPONENT},
    errorloadingcourses: {key: "errorloadingcourses", component: COMPONENT},
    filterall: {key: "allcourses", component: COMPONENT},
    filterallincludinghidden: {key: "allincludinghidden", component: COMPONENT},
    filtercustomfield: {key: "customfield", component: COMPONENT},
    filterfavourites: {key: "favourites", component: COMPONENT},
    filterfuture: {key: "future", component: COMPONENT},
    filterhidden: {key: "hiddencourses", component: COMPONENT},
    filterinprogress: {key: "inprogress", component: COMPONENT},
    filterpast: {key: "past", component: COMPONENT},
    filterresults: {key: "aria:groupingdropdown", component: COMPONENT},
    filters: {key: "filters"},
    hidecourse: {key: "hidecourse", component: COMPONENT},
    managecategories: {key: "managecategories"},
    managecourses: {key: "managecourses"},
    nextpage: {key: "nextpage"},
    percentcomplete: {key: "completepercent", component: COMPONENT},
    previouspage: {key: "previouspage"},
    removefromstarred: {key: "aria:removefromfavouritesfor", component: COMPONENT},
    requestcoursebutton: {key: "requestcoursebutton", component: COMPONENT},
    search: {key: "search"},
    searchcourses: {key: "searchcourses", component: COMPONENT},
    showcourse: {key: "show", component: COMPONENT},
    sortby: {key: "sortby"},
    sortcoursename: {key: "title", component: COMPONENT},
    sortcourses: {key: "aria:sortingdropdown", component: COMPONENT},
    sortlastaccessed: {key: "lastaccessed", component: COMPONENT},
    sortshortname: {key: "shortname", component: COMPONENT},
    sortstartdate: {key: "startdate"},
    starcourse: {key: "aria:addtofavouritesfor", component: COMPONENT},
    tooltipfilter: {key: "filter"},
    tooltipsort: {key: "sort"},
    tooltipview: {key: "view"},
    viewcard: {key: "card", component: COMPONENT},
    viewlabel: {key: "view"},
    viewlist: {key: "list", component: COMPONENT},
    viewsummary: {key: "summary", component: COMPONENT},
};

/**
 * Load every UI string the block needs in one batched request.
 *
 * @returns The resolved Strings map.
 */
export async function loadStrings(): Promise<Strings> {
    const keys = Object.keys(STRING_MAP) as Array<keyof Strings>;
    const values = await getStrings(keys.map((k) => STRING_MAP[k]));
    const strings = {} as Record<keyof Strings, string>;
    keys.forEach((k, i) => {
        strings[k] = values[i];
    });
    return strings;
}
