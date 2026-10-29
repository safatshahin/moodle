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
 * Dashboard web-service repository.
 *
 * @module     core_my/repository
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {GridLimits, LayoutItem} from './layout';
import {fetchOne} from '@moodle/lms/core/ajax';

export type DashboardBlock = {
    id: number;
    name: string;
    title: string;
    content: string;
    footer: string;
    region: string;
    weight: number;
    controls: string;
    visible: boolean;
};

export type AvailableBlock = {
    name: string;
    title: string;
    /** The class of the form shown when adding this block type, or null when it has none. */
    blockform?: string | null;
};

/**
 * Control icons, rendered by the server through the theme's icon system ($OUTPUT->pix_icon()),
 * so that the theme's icon map applies and no FontAwesome class names are hardcoded here.
 */
export type DashboardIcons = {
    move: string;
    add: string;
    up: string;
    down: string;
    left: string;
    right: string;
};

export type DashboardData = {
    blocks: DashboardBlock[];
    layout: LayoutItem[];
    availableblocks: AvailableBlock[];
    canedit: boolean;
    editing: boolean;
    sitedefault: boolean;
    limits: GridLimits;
    icons: DashboardIcons;
    javascript: string;
};

type UpdateResult = {
    status: boolean;
    blockid: number;
};

export const getDashboard = async (siteDefault: boolean): Promise<DashboardData> => fetchOne<DashboardData>({
    methodname: 'core_my_get_dashboard',
    args: {sitedefault: siteDefault},
});

/**
 * The block types the user may add to the page identified by the edited-page hash, fetched when
 * the "Add a block" modal opens, exactly as core_block/add_modal does on every other page: the
 * list depends on configuration an administrator may change while the dashboard is open (which
 * blocks are unaddable, which capabilities a role has), so the one fetched with the page is only
 * a fallback for when there is no page hash.
 */
export const fetchAddableBlocks = async (pagehash: string): Promise<AvailableBlock[]> => fetchOne<AvailableBlock[]>({
    methodname: 'core_block_fetch_addable_blocks',
    args: {
        pagecontextid: 0, pagetype: '', pagelayout: '', subpage: '', pagehash,
    },
});

export const updateDashboard = async (
    action: 'save' | 'add' | 'reset',
    siteDefault: boolean,
    layout: LayoutItem[] = [],
    blockname = '',
): Promise<UpdateResult> => fetchOne<UpdateResult>({
    methodname: 'core_my_update_dashboard',
    args: {
        action,
        sitedefault: siteDefault,
        layout,
        blockname,
    },
});
