// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Flexible responsive dashboard React application.
 *
 * @module     core_my/index
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {
    useCallback,
    useEffect,
    useLayoutEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import {Button} from '@moodlehq/design-system';
import DashboardTile from './components/DashboardTile';
import ConfirmationDialog from './components/ConfirmationDialog';
import Icon from './components/Icon';
import GridCell from './components/GridCell';
import DashboardLoading from './components/DashboardLoading';
import {
    DEFAULT_LIMITS,
    GRID_GAP,
    ROW_HEIGHT,
    clampToGrid,
    fitToFreeSpace,
    columnsForWidth,
    disturbedCount,
    emptyRegions,
    maxRow,
    packLayout,
    packWithPinned,
    writeBack,
    type GridLimits,
    type LayoutItem,
} from './layout';
import {
    fetchAddableBlocks,
    getDashboard,
    updateDashboard,
    type AvailableBlock,
    type DashboardData,
} from './repository';
import {useDashboardLabels, type DashboardLabels} from './strings';
import {addBlockFallbackUrl, openAddBlockModal} from './addBlockModal';
import Pending from '@moodle/lms/core/pending';
import {requireManyAsync} from '@moodle/lms/core/amd';
import {isMoodleAjaxError} from '@moodle/lms/core/ajax';
import {getString} from '@moodle/lms/core/stringUtils';

type DashboardErrorBoundaryProps = {
    children: React.ReactNode;
    resetLabel?: string;
    onReset?: () => void;
};

type DashboardErrorBoundaryState = {
    error: Error | null;
};

/**
 * Keep a rendering failure (for instance a saved layout the packing helpers cannot place) from
 * taking the whole page down with it: report it, and keep the way out - resetting the dashboard to
 * the site default - reachable, since there is no legacy dashboard to fall back to.
 */
export class DashboardErrorBoundary extends React.Component<DashboardErrorBoundaryProps, DashboardErrorBoundaryState> {
    static getDerivedStateFromError(error: Error): DashboardErrorBoundaryState {
        return {error};
    }

    state: DashboardErrorBoundaryState = {error: null};

    componentDidCatch(error: Error): void {
        console.error('[core_my/dashboard] Rendering failed:', error);
    }

    render(): React.ReactNode {
        if (!this.state.error) {
            return this.props.children;
        }

        return <div className='core-my-dashboard-status alert alert-danger' role='alert'>
            <p>{this.state.error.message}</p>
            {this.props.onReset && this.props.resetLabel !== undefined && this.props.resetLabel !== '' && <Button
                variant='outline-danger'
                label={this.props.resetLabel}
                onClick={this.props.onReset}
            />}
        </div>;
    }
}

type Interaction = {
    id: number;
    mode: 'move' | 'resize';
    origin: 'keyboard' | 'mouseclick' | 'pointer';
    original: LayoutItem;
    draft: LayoutItem;
    before: LayoutItem[];
    drag?: PointerDrag;
};

type BoardCellsProps = {
    rows: number;
    columnCount: number;
    layout: LayoutItem[];
    /** Whether something is being moved or resized. */
    active: boolean;
    interaction: Interaction | null;
    prospective?: LayoutItem;
    labels: DashboardLabels;
    cueIcon: string;
    onActivate: (column: number, row: number) => void;
    onPlace: (column: number, row: number) => void;
};

/**
 * The grid's empty space in edit mode, in one of two shapes.
 *
 * At rest, the empty cells are merged into regions (see emptyRegions), each one button that adds a
 * block there. While a block is being moved or resized, every grid unit shows as an active cell so
 * all the snap points are visible, and the ones under the footprint the block would take on
 * commit become the drop preview while the rest shrink back. After a click or tap on a handle
 * (origin 'mouseclick'), clicking an active cell places the block there. Hovering one does nothing:
 * the tile moves only when told to, as with the arrow keys, or stepping it with the on-screen arrows
 * would leave the pointer over the cell it just left, and pull it straight back.
 */
const BoardCells = ({
    rows,
    columnCount,
    layout,
    active,
    interaction,
    prospective,
    labels,
    cueIcon,
    onActivate,
    onPlace,
}: BoardCellsProps) => {
    if (!active) {
        return <>
            {emptyRegions(layout, columnCount, rows).map(region => <GridCell
                key={`${region.column}:${region.row}`}
                column={region.column}
                row={region.row}
                columns={region.columns}
                rows={region.rows}
                label={labels.addblockhere}
                cueLabel={labels.addblockhere}
                cueIcon={cueIcon}
                onActivate={onActivate}
            />)}
        </>;
    }

    const placing = interaction?.origin === 'mouseclick';
    // The drop preview is drawn on the active cells themselves: those under the footprint keep their
    // full size and take the preview's tint, and every other cell shrinks back from it, so the
    // preview stands out without growing. How far a cell is from the footprint (in whole cells, on
    // whichever axis is further) staggers its change in my.scss, so the cells nearest the preview
    // move first and the change ripples outwards rather than happening all at once.
    const footprint = prospective;
    const distance = (column: number, row: number): number | undefined => {
        if (!footprint) {
            return undefined;
        }

        const across = Math.max(footprint.column - column, column - (footprint.column + footprint.columns - 1), 0);
        const down = Math.max(footprint.row - row, row - (footprint.row + footprint.rows - 1), 0);
        return Math.max(across, down);
    };

    return <>
        {Array.from({length: rows}, (_, row) => Array.from({length: columnCount}, (__, column) => <GridCell
            key={`${column}:${row}`}
            column={column}
            row={row}
            state='active'
            previewDistance={distance(column, row)}
            onPlace={placing ? onPlace : undefined}
        />))}
    </>;
};

type PointerDrag = {
    x: number;
    y: number;
    width?: number;
    height?: number;
    shrinking?: boolean;
};

/** Where a newly-added block should go: a specific empty cell, or the start or end of the grid. */
type AddTarget = {
    column?: number;
    row?: number;
    position?: 'end';
};

type ConfirmAction = {type: 'reset'};

// See the settling MutationObserver in the collected-JS effect below for what these guard.
const DASHBOARD_SETTLE_QUIET_MS = 250;
const DASHBOARD_SETTLE_TIMEOUT_MS = 5000;
let dashboardSettleCounter = 0;

/**
 * Whether this is the site-default dashboard. The page passes it as a prop (see
 * dashboard::render_mount_point()); the URL is only consulted when the component is mounted some
 * other way.
 */
const resolveSiteDefault = (property?: boolean): boolean =>
    property ?? globalThis.location.pathname.endsWith('/my/indexsys.php');

/**
 * The grid's bounds come from the server with every payload (see layout.ts); the defaults only
 * stand in until the first one arrives.
 */
const resolveLimits = (data: DashboardData | null): GridLimits => data?.limits ?? DEFAULT_LIMITS;

type DashboardPlaceholderProps = {
    error: string;
    loadingLabel: string;
    initialLayout: LayoutItem[];
};

/** What stands in for the dashboard until its data and strings have loaded, or if loading failed. */
const DashboardPlaceholder = ({error, loadingLabel, initialLayout}: DashboardPlaceholderProps) => error === ''
    ? <DashboardLoading label={loadingLabel} layout={initialLayout} />
    : <div className='core-my-dashboard-status alert alert-danger' role='alert'>{error}</div>;

/**
 * The message to show for a failed request: a web service error (a plain object carrying the
 * server's message and error code, not an Error instance) reads as its message, never as
 * "[object Object]".
 */
const describeError = (caught: unknown): string => {
    if (caught instanceof Error || isMoodleAjaxError(caught)) {
        return caught.message;
    }

    return String(caught);
};

/**
 * Where the drop preview shows: for a pointer drag or resize, wherever its draft is, including back
 * over its starting place (the tile itself is following the pointer, so the preview is the only thing
 * showing where it will drop, and hiding it there would make the grid's cells grow back and shrink
 * again as it passes). The arrow keys, the on-screen arrows and click-to-place have none: they move
 * the tile itself, a cell at a time, so it would only sit under the tile.
 */
const prospectiveFor = (interaction: Interaction | null) =>
    (interaction?.origin === 'pointer' ? interaction.draft : undefined);

/**
 * How many rows the grid shows: enough for the layout, plus one spare row while a block is moved by
 * pointer, click or tap, so it can be put below everything else. The arrow keys need none: moving
 * down grows the grid by itself.
 */
const gridRows = (layout: LayoutItem[], interaction: Interaction | null): number =>
    Math.max(1, maxRow(layout)) + (interaction?.mode === 'move' && interaction.origin !== 'keyboard' ? 1 : 0);

/** Whether an element is laid out right to left, where the grid's first column is on the right. */
const isRtl = (element: Element | null): boolean =>
    Boolean(element) && getComputedStyle(element!).direction === 'rtl';

const layoutChanged = (original: LayoutItem, draft: LayoutItem): boolean =>
    original.column !== draft.column || original.row !== draft.row
    || original.columns !== draft.columns || original.rows !== draft.rows;

/**
 * Run a freshly loaded dashboard's collected block JavaScript, and report the dashboard as
 * pending (via core/pending) until any resulting DOM churn settles.
 *
 * A block's own async fetch-and-replace of its rendered content (e.g. Course overview, Recently
 * accessed items) is invisible to anything watching M.util.pending_js - Behat's
 * wait_for_pending_js() included - unless that block's own code says so, which this project's
 * acceptance criteria rules out asking every block to do (a backwards-compatible block API
 * extension to do this properly is filed as a follow-up on the parent epic). Detect it
 * generically instead: such a replacement is always a structural (childList) DOM mutation
 * somewhere under the grid, so treat the dashboard as still settling from the moment this
 * reload's collected JS starts running until the grid goes quiet for a short window - bounded by
 * a hard ceiling in case some future block's own mutations (or a genuine failure) never quiesce,
 * so this can never hang a caller indefinitely. Only childList is watched (not
 * attributes/characterData), so unrelated noise - drag positioning, resize-driven style changes -
 * never resets it.
 *
 * @param data The current dashboard payload, or null before the first load resolves.
 * @param gridReference The grid element to watch for mutations under.
 */
const useCollectedBlockJavascript = (
    data: DashboardData | null,
    gridReference: React.RefObject<HTMLDivElement | null>,
): void => {
    useEffect(() => {
        if (!data || data.javascript === '') {
            return undefined;
        }

        // Each dashboard reload re-renders blocks with fresh DOM ids and JS initialisers.
        // Guard against a superseded reload's async script running after a newer one has
        // already replaced the tile content it was written to target.
        let superseded = false;

        let settled = false;
        let quietTimer: ReturnType<typeof setTimeout> | undefined;
        let observer: MutationObserver | undefined;
        const pending = new Pending(`core_my/dashboard:settling:${dashboardSettleCounter++}`);
        const settle = () => {
            if (settled) {
                return;
            }

            settled = true;
            clearTimeout(quietTimer);
            clearTimeout(hardTimer);
            observer?.disconnect();
            pending.resolve();
        };

        const hardTimer = setTimeout(settle, DASHBOARD_SETTLE_TIMEOUT_MS);
        const grid = gridReference.current;
        if (grid) {
            observer = new MutationObserver(() => {
                clearTimeout(quietTimer);
                quietTimer = setTimeout(settle, DASHBOARD_SETTLE_QUIET_MS);
            });
            observer.observe(grid, {childList: true, subtree: true});
        }

        // Nothing may ever mutate at all (every block already rendered its final content
        // server-side) - start the quiet window immediately too, not only on the observer's
        // first callback.
        quietTimer = setTimeout(settle, DASHBOARD_SETTLE_QUIET_MS);

        void requireManyAsync(['core/fragment', 'core/templates']).then(([fragment, templates]) => {
            if (superseded) {
                return undefined;
            }

            const processed = (fragment as {processCollectedJavascript: (source: string) => string})
                .processCollectedJavascript(data.javascript);
            (templates as {runTemplateJS: (source: string) => void}).runTemplateJS(processed);
            return undefined;
        });
        return () => {
            superseded = true;
            settle();
        };
    }, [data]);
};

/**
 * Track the grid's current responsive column count from its own rendered width.
 *
 * Returns both the reactive state (for rendering/memoization) and a ref mirroring the same value
 * (for imperative reads from callbacks - e.g. commit() in Dashboard below - that must see the
 * latest column count even when invoked from a stale closure, such as a pointerup listener
 * registered several renders ago).
 *
 * @param gridReference The grid element to measure.
 * @param mounted Whether the grid is rendered at all: the measurement is (re)attached whenever
 * this changes, and whenever a fresh payload loads.
 * @param data The current dashboard payload; re-measures whenever a fresh one loads.
 * @returns A tuple of [columnCount, columnCountRef].
 */
const useResponsiveColumnCount = (
    gridReference: React.RefObject<HTMLDivElement | null>,
    mounted: boolean,
    data: DashboardData | null,
): [number, React.RefObject<number>] => {
    const [columnCount, setColumnCount] = useState(1);
    const columnCountReference = useRef(1);

    // UseLayoutEffect, not useEffect: the initial columnCount=1 default must be corrected
    // before the browser paints, not after - otherwise the grid visibly repacks from one
    // column to the real column count on every load, a large, confirmed layout shift.
    // The grid only exists once both the payload and the strings have arrived, in either
    // order, so `mounted` is a dependency too: without it, a payload that lands before the
    // strings would run this while the loading placeholder is still showing, find no grid, and
    // never look again.
    useLayoutEffect(() => {
        const grid = gridReference.current;
        if (!mounted || !grid) {
            return undefined;
        }

        const measure = () => {
            const next = columnsForWidth(grid.getBoundingClientRect().width);
            columnCountReference.current = next;
            setColumnCount(next);
        };

        const observer = new ResizeObserver(measure);
        observer.observe(grid);
        measure();
        return () => {
            observer.disconnect();
        };
    }, [mounted, data]);

    return [columnCount, columnCountReference];
};

type DashboardProps = {
    loadingLabel?: string;
    initialLayout?: LayoutItem[];
    /** Whether this is the site-default dashboard (my/indexsys.php) rather than the user's own. */
    siteDefault?: boolean;
    /**
     * The page's edited-page hash (see moodle_page::get_edited_page_hash()), which a block type's
     * own add form needs to find this page again; null when the page is not being edited.
     */
    pagehash?: string | null;
};

const Dashboard = ({
    loadingLabel = '',
    initialLayout = [],
    siteDefault: siteDefaultProperty,
    pagehash = null,
}: DashboardProps) => {
    const [data, setData] = useState<DashboardData | null>(null);
    const [canonical, setCanonical] = useState<LayoutItem[]>([]);
    const [interaction, setInteraction] = useState<Interaction | null>(null);
    const [announcement, setAnnouncement] = useState({text: '', serial: 0});
    const [error, setError] = useState('');
    const [confirmAction, setConfirmAction] = useState<ConfirmAction | null>(null);
    const [saving, setSaving] = useState(false);
    const gridReference = useRef<HTMLDivElement>(null);
    const pointerReference = useRef<{x: number; y: number; moved: boolean} | null>(null);
    const interactionReference = useRef<Interaction | null>(null);
    const canonicalReference = useRef<LayoutItem[]>([]);
    const displayLayoutReference = useRef<LayoutItem[]>([]);
    const dataReference = useRef<DashboardData | null>(null);
    const siteDefault = resolveSiteDefault(siteDefaultProperty);
    const labels = useDashboardLabels();
    const limits = resolveLimits(data);
    const limitsReference = useRef<GridLimits>(limits);
    limitsReference.current = limits;

    /**
     * Fetch a full dashboard payload and reset the canonical layout to match it.
     *
     * Called on mount and after any server-side mutation the client cannot safely predict the
     * result of (adding a block, removing one, resetting to the site default) - see get_dashboard.
     */
    const load = useCallback(async () => {
        // Reported as pending JavaScript (core/pending) so that anything waiting for the page to
        // settle - Behat's wait_for_pending_js() above all - waits for the payload too, rather than
        // looking for blocks while the loading skeleton is still up.
        const pending = new Pending('core_my/dashboard:load');
        try {
            const response = await getDashboard(siteDefault);
            setData(response);
            dataReference.current = response;
            setCanonical(response.layout);
            canonicalReference.current = response.layout;
            setError('');
            return response;
        } catch (error_) {
            setError(describeError(error_));
            return null;
        } finally {
            pending.resolve();
        }
    }, [siteDefault]);

    useEffect(() => {
        void load();
    }, [load]);

    // The blocks' collected JavaScript looks for their markup, which is only on the page once the
    // grid is drawn, and that needs the strings as well as the data: whichever arrives last, the
    // JavaScript runs after it.
    useCollectedBlockJavascript(labels ? data : null, gridReference);
    const [columnCount, columnCountReference] = useResponsiveColumnCount(gridReference, Boolean(data && labels), data);

    const displayLayout = useMemo(() => packLayout(canonical, columnCount), [canonical, columnCount]);
    displayLayoutReference.current = displayLayout;
    const previewLayout = useMemo(() => interaction
        ? packWithPinned(displayLayout, columnCount, interaction.draft, limits)
        : displayLayout, [columnCount, displayLayout, interaction, limits]);
    const bumpedBlockIds = useMemo(() => {
        if (interaction?.origin !== 'pointer') {
            return new Set<number>();
        }

        const originalItems = new Map(displayLayout.map(item => [item.id, item]));
        return new Set(previewLayout.filter(item => {
            const original = originalItems.get(item.id);
            return item.id !== interaction.id && original && (
                item.column !== original.column || item.row !== original.row
            );
        }).map(item => item.id));
    }, [displayLayout, interaction, previewLayout]);
    const blocksById = useMemo(() => new Map((data?.blocks ?? []).map(block => [block.id, block])), [data]);

    /**
     * Set the live region's text so screen readers announce a move/resize outcome. Every
     * announcement gets a new serial number, which keys the element holding the text: a message
     * identical to the one before it (the same limit reached again, the same edge of the grid)
     * then still replaces that element, and is spoken again rather than met with silence.
     */
    const announce = useCallback(async (key: string, value?: string | Record<string, unknown>) => {
        const text = await getString(key, 'my', value);
        setAnnouncement(previous => ({text, serial: previous.serial + 1}));
    }, []);

    /**
     * Begin a move or resize interaction on a tile.
     *
     * `origin` distinguishes how the interaction started (keyboard activation, a mouse click that
     * turns out not to be a drag, or an active pointer drag) because several things downstream -
     * whether the on-screen move/resize controls are shown, whether other tiles animate out of the
     * way live, whether Escape/Enter apply - only make sense for some of these (see showControls
     * and shouldAnimatePosition where Interaction is consumed further down).
     */
    const start = useCallback((id: number, mode: 'move' | 'resize', origin: Interaction['origin'] = 'keyboard') => {
        const item = displayLayout.find(candidate => candidate.id === id);
        const block = blocksById.get(id);
        if (!item || !block) {
            return;
        }

        const next = {
            id, mode, origin, original: item, draft: item, before: displayLayout,
        };
        interactionReference.current = next;
        setInteraction(next);
        void announce(mode === 'move' ? 'dashboardmovebegin' : 'dashboardresizebegin', block.title);
    }, [announce, blocksById, displayLayout]);

    /**
     * Nudge the in-progress interaction's draft rectangle by whole grid cells - one keypress, one
     * cell. (pointerDown's own `move` handler needs sub-cell drag position for the tile to track
     * the pointer smoothly, so it recomputes the equivalent clamping itself rather than reusing
     * this; see the comment there.) A resize that would fall below the minimum tile size is
     * rejected (with an announcement) rather than clamped, so the keyboard controls never go
     * silently unresponsive at the limit; every other edge (the grid's right edge and its row
     * ceiling) simply stops the nudge.
     */
    const shift = useCallback((visualHorizontal: number, vertical: number) => {
        const {current} = interactionReference;
        if (!current) {
            return;
        }

        const horizontal = isRtl(gridReference.current) ? -visualHorizontal : visualHorizontal;
        const bounds = limitsReference.current;
        let draft = {...current.draft};
        if (current.mode === 'move') {
            draft.column += horizontal;
            draft.row += vertical;
        } else {
            const nextColumns = draft.columns + horizontal;
            const nextRows = draft.rows + vertical;
            if (nextColumns < bounds.mincolumns || nextRows < bounds.minrows) {
                void announce('dashboardminimumsize', `${bounds.mincolumns} × ${bounds.minrows}`);
                return;
            }

            draft.columns = nextColumns;
            draft.rows = nextRows;
        }

        draft = clampToGrid(draft, columnCount, bounds);
        if (!layoutChanged(current.draft, draft)) {
            // The grid's edge: a move says where the block still is, so the press is not met with silence.
            if (current.mode === 'move') {
                void announce('gridcellposition', {row: draft.row + 1, column: draft.column + 1});
            }

            return;
        }

        const next = {...current, draft};
        interactionReference.current = next;
        setInteraction(next);
        if (current.mode === 'move') {
            void announce('gridcellposition', {row: draft.row + 1, column: draft.column + 1});
        } else {
            void announce('dashboardresizesize', {columns: draft.columns, rows: draft.rows});
        }
    }, [announce, columnCount]);

    // Keep a block moved or resized with the keyboard in view, step by step, as a pointer drag does
    // by following the pointer. 'nearest' scrolls only when it has left the screen.
    useEffect(() => {
        if (interaction?.origin !== 'keyboard') {
            return;
        }

        const selector = `.core-my-dashboard-tile[data-block-id="${CSS.escape(String(interaction.id))}"]`;
        gridReference.current?.querySelector<HTMLElement>(selector)?.scrollIntoView?.({block: 'nearest', inline: 'nearest'});
    }, [interaction?.origin, interaction?.id, interaction?.draft]);

    /**
     * Persist the in-progress interaction's draft rectangle as the new canonical layout.
     *
     * Reads from refs (currentData/currentDisplay/currentCanonical/currentColumns), not state,
     * because this can be invoked from a pointerup listener registered once per drag in
     * pointerDown's closure - by the time the user releases, several renders may have happened
     * and captured state there would be stale. packWithPinned resolves any other tiles the
     * committed rectangle now overlaps (recorded in `disturbed`, purely for the a11y announcement);
     * writeBack then maps the result back to the full-width canonical layout: restoring every
     * item's canonical column span, undoing any margin shift the current column count applied,
     * or - when the current width is a genuine reflow, as on a phone - carrying over only the
     * new block order, so the persisted layout matches what was just resolved on-screen rather
     * than persisting the narrow stack as the wide layout (see the writeBack/packLayout comments
     * in layout.ts).
     */
    const commit = useCallback(async () => {
        const {current} = interactionReference;
        const currentData = dataReference.current;
        if (!current || !currentData) {
            return;
        }

        const currentDisplay = displayLayoutReference.current;
        const currentCanonical = canonicalReference.current;
        const currentColumns = columnCountReference.current;
        const bounds = limitsReference.current;

        const derived = packWithPinned(currentDisplay, currentColumns, current.draft, bounds);
        const disturbed = disturbedCount(current.before, derived, current.id);
        const next = writeBack(currentCanonical, derived, current.id, bounds, currentColumns);
        setSaving(true);
        try {
            await updateDashboard('save', siteDefault, next);
            setCanonical(next);
            canonicalReference.current = next;
            const item = derived.find(candidate => candidate.id === current.id)!;
            await announce(current.mode === 'move' ? 'dashboardmovecommitted' : 'dashboardresizecommitted', {
                row: item.row + 1,
                column: item.column + 1,
                columns: item.columns,
                rows: item.rows,
                disturbed,
            });
            interactionReference.current = null;
            setInteraction(null);
        } catch (error_) {
            setError(describeError(error_));
        } finally {
            setSaving(false);
        }
    }, [announce, siteDefault]);

    /** Abandon the in-progress interaction, discarding its draft rectangle unsaved. */
    const cancel = useCallback(() => {
        if (interaction) {
            void announce('dashboardoperationdiscarded');
        }

        interactionReference.current = null;
        setInteraction(null);
    }, [announce, interaction]);

    /**
     * Point the in-progress interaction at a grid cell, as when a block picked up by a click or a
     * tap is shown over the cell under the pointer: a move puts the block's corner there, a resize
     * stretches the block to reach it. Returns the interaction it set, or null when there is none.
     */
    const target = useCallback((column: number, row: number): Interaction | null => {
        const {current} = interactionReference;
        if (!current) {
            return null;
        }

        const draft = clampToGrid(current.mode === 'move'
            ? {...current.draft, column, row}
            : {
                ...current.draft,
                columns: column - current.original.column + 1,
                rows: row - current.original.row + 1,
            }, columnCount, limitsReference.current);
        if (!layoutChanged(current.draft, draft)) {
            return current;
        }

        const next = {...current, draft};
        interactionReference.current = next;
        setInteraction(next);
        return next;
    }, [columnCount]);

    /** Place a block picked up by a click or a tap in the chosen cell, and save. */
    const place = useCallback((column: number, row: number) => {
        if (target(column, row)) {
            void commit();
        }
    }, [commit, target]);

    /**
     * Keyboard-driven move/resize: Space/Enter starts or commits an interaction, Escape cancels
     * it, and the arrow keys nudge it one cell via shift() - a full keyboard-only equivalent of
     * the pointer drag pointerDown offers with a mouse.
     */
    const keyDown = useCallback((event: React.KeyboardEvent, id: number, mode: 'move' | 'resize') => {
        if (!interaction && (event.key === ' ' || event.key === 'Enter')) {
            event.preventDefault();
            start(id, mode);
            return;
        }

        if (interaction?.id !== id || interaction.mode !== mode) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            cancel();
        } else if (event.key === ' ' || event.key === 'Enter') {
            event.preventDefault();
            void commit();
        } else if (event.key.startsWith('Arrow')) {
            event.preventDefault();
            const directions: Record<string, [number, number]> = {
                ArrowLeft: [-1, 0],
                ArrowRight: [1, 0],
                ArrowUp: [0, -1],
                ArrowDown: [0, 1],
            };
            shift(...directions[event.key]);
        }
    }, [cancel, commit, interaction, shift, start]);

    /**
     * Mouse/touch-driven move or resize, tracked with raw window pointer listeners rather than
     * React drag events so the drag keeps following the pointer even outside the tile or the grid.
     *
     * Two coordinate systems are maintained together throughout the drag: `draft` (whole grid
     * cells - column/row/columns/rows, exactly what shift()/keyDown also produce, and the only
     * form the server ever sees) and `drag` (raw sub-cell pixel offsets/dimensions, used only to
     * animate the dragged tile smoothly under the pointer - see DashboardTile). `columnStride` and
     * `rowStride` are one cell plus one gap each, i.e. the pixel distance between equivalent points
     * on adjacent cells; dividing a pixel delta by a stride and rounding is what turns a drag
     * distance into a whole number of cells for `draft`. A move is capped so the tile's whole
     * rectangle - not just its origin corner - stays on the grid, horizontally and vertically (the
     * row ceiling the server also enforces); a resize is capped so it never shrinks below the
     * minimum tile size and never grows past the grid's right edge or its row ceiling.
     * `pointer.moved` (a small 4px threshold, to absorb hand tremor on a click that was never
     * meant to be a drag) is what pointerup below uses to decide whether this was a genuine drag
     * to commit, or a plain click that should instead just reveal the on-screen move/resize
     * controls (see the `up` handler and Interaction.origin).
     */
    const pointerDown = useCallback((event: React.PointerEvent, id: number, mode: 'move' | 'resize') => {
        event.preventDefault();
        start(id, mode, 'pointer');
        pointerReference.current = {x: event.clientX, y: event.clientY, moved: false};
        const origin = displayLayout.find(item => item.id === id);
        if (!origin) {
            return;
        }

        const bounds = limitsReference.current;
        const grid = gridReference.current;
        const cellWidth = grid
            ? (grid.getBoundingClientRect().width - (GRID_GAP * (columnCount - 1))) / columnCount
            : 1;
        const columnStride = cellWidth + GRID_GAP;
        const rowStride = ROW_HEIGHT + GRID_GAP;
        const originalWidth = (origin.columns * cellWidth) + ((origin.columns - 1) * GRID_GAP);
        const originalHeight = (origin.rows * ROW_HEIGHT) + ((origin.rows - 1) * GRID_GAP);
        const move = (pointerEvent: PointerEvent) => {
            const pointer = pointerReference.current;
            if (!pointer) {
                return;
            }

            const deltaX = pointerEvent.clientX - pointer.x;
            const deltaY = pointerEvent.clientY - pointer.y;
            pointer.moved = pointer.moved || Math.abs(deltaX) >= 4 || Math.abs(deltaY) >= 4;
            const horizontal = Math.round(deltaX / columnStride);
            const vertical = Math.round(deltaY / rowStride);
            setInteraction(current => {
                if (!current) {
                    return current;
                }

                let draft = {...origin};
                let drag: PointerDrag;
                if (mode === 'move') {
                    draft.column = origin.column + horizontal;
                    draft.row = origin.row + vertical;
                    drag = {
                        x: Math.max(
                            -origin.column * columnStride,
                            Math.min((columnCount - origin.column - origin.columns) * columnStride, deltaX),
                        ),
                        y: Math.max(
                            -origin.row * rowStride,
                            Math.min((bounds.maxrows - origin.row - origin.rows) * rowStride, deltaY),
                        ),
                    };
                } else {
                    draft.columns = origin.columns + horizontal;
                    draft.rows = origin.rows + vertical;
                    drag = {
                        x: 0,
                        y: 0,
                        width: Math.max(cellWidth, Math.min(
                            ((columnCount - origin.column) * columnStride) - GRID_GAP,
                            originalWidth + deltaX,
                        )),
                        height: Math.max(ROW_HEIGHT, Math.min(
                            ((bounds.maxrows - origin.row) * rowStride) - GRID_GAP,
                            originalHeight + deltaY,
                        )),
                        shrinking: deltaX < 0 || deltaY < 0,
                    };
                }

                draft = clampToGrid(draft, columnCount, bounds);
                const next = {...current, draft, drag};
                interactionReference.current = next;
                return next;
            });
        };

        const cleanup = () => {
            globalThis.removeEventListener('pointermove', move);
            globalThis.removeEventListener('pointerup', up);
            globalThis.removeEventListener('pointercancel', abort);
            pointerReference.current = null;
        };

        const up = () => {
            const {current} = interactionReference;
            if (pointerReference.current?.moved && current && layoutChanged(current.original, current.draft)) {
                void commit();
            } else if (pointerReference.current?.moved) {
                cancel();
            } else {
                setInteraction(previous => {
                    if (!previous) {
                        return previous;
                    }

                    // A click, not a drag: forget any sub-threshold pointer travel. Left in place,
                    // `drag` kept the tile drawn at its starting cell (see DashboardTile's
                    // dragOrigin) while the arrows moved only its footprint.
                    const next = {
                        ...previous, origin: 'mouseclick' as const, drag: undefined, draft: previous.original,
                    };
                    interactionReference.current = next;
                    return next;
                });
            }

            cleanup();
        };

        const abort = () => {
            cleanup();
            cancel();
        };

        globalThis.addEventListener('pointermove', move);
        globalThis.addEventListener('pointerup', up);
        globalThis.addEventListener('pointercancel', abort);
    }, [cancel, columnCount, commit, displayLayout, start]);

    /**
     * Place a block that has just been added where the user asked for it.
     *
     * A newly-added block's id (when it came through its own add form), default size and rendered
     * content are only known once the server has it, so this cannot be done optimistically: it
     * reloads via load() first, finds the new block in the fresh layout (by id when known,
     * otherwise as the one block the previous payload did not have), then places it in the
     * on-screen layout and saves again:
     *
     * into a specific empty cell (target.column/row), sized to the free space there
     * (fitToFreeSpace) so that none of its neighbours has to move - or, when not even the
     * smallest tile fits that hole, pinned there at its default size with the neighbours
     * displaced around it, as a move would;
     *
     * at the end (target.position === 'end'), pinned below everything else, in the first column.
     *
     * The end case is pinned too, rather than left where the server appended it, so that the
     * saved column is always one the user can see at the current width: the server does not know
     * that width, and a block saved outside the window of columns the user is looking at would
     * turn their whole view into a reflow (see writeBack()).
     *
     * Reads the column count from its ref, not state: this runs from the add modal's callbacks,
     * created when the modal opened, and the grid may have been re-measured since.
     */
    const placeNewBlock = useCallback(async (blockid: number | null, destination: AddTarget, title: string) => {
        const previousIds = new Set((dataReference.current?.blocks ?? []).map(block => block.id));
        const response = await load();
        if (!response) {
            return;
        }

        const bounds = response.limits;
        const currentColumns = columnCountReference.current;
        const item = blockid === null
            ? response.layout.filter(candidate => !previousIds.has(candidate.id))
                .sort((left, right) => right.id - left.id)[0]
            : response.layout.find(candidate => candidate.id === blockid);
        if (item) {
            const display = packLayout(response.layout, currentColumns);
            const others = display.filter(candidate => candidate.id !== item.id);
            let pinned: LayoutItem;
            if (destination.column !== undefined && destination.row !== undefined) {
                const wanted = clampToGrid({...item, column: destination.column, row: destination.row}, currentColumns, bounds);
                const fitted = fitToFreeSpace(others, currentColumns, wanted, bounds);
                pinned = fitted ? {...item, ...fitted} : wanted;
            } else {
                pinned = {...item, column: 0, row: maxRow(others)};
            }

            const derived = packWithPinned(display, currentColumns, pinned, bounds);
            const next = writeBack(response.layout, derived, item.id, bounds, currentColumns);
            await updateDashboard('save', siteDefault, next);
            setCanonical(next);
            canonicalReference.current = next;
        }

        await announce('dashboardblockadded', title);
    }, [announce, columnCountReference, load, siteDefault]);

    /** Add a block type with no add form of its own over the web service, then place it. */
    const add = useCallback(async (block: AvailableBlock, destination: AddTarget) => {
        setSaving(true);
        try {
            const result = await updateDashboard('add', siteDefault, [], block.name);
            await placeNewBlock(result.blockid > 0 ? result.blockid : null, destination, block.title);
        } catch (error_) {
            setError(describeError(error_));
        } finally {
            setSaving(false);
        }
    }, [placeNewBlock, siteDefault]);

    /** A block added through its own form (see addBlockModal.ts) already exists: reload and place it. */
    const placeFormAdded = useCallback(async (block: AvailableBlock, destination: AddTarget) => {
        setSaving(true);
        try {
            await placeNewBlock(null, destination, block.title);
        } catch (error_) {
            setError(describeError(error_));
        } finally {
            setSaving(false);
        }
    }, [placeNewBlock]);

    /**
     * Open the standard "Add a block" modal (see addBlockModal.ts) for a destination position: an empty
     * cell, the start of the grid, or its end.
     */
    const openAddBlock = useCallback(async (destination: AddTarget, trigger?: Element | null) => {
        const {current} = dataReference;
        if (!current || !labels) {
            return;
        }

        try {
            // The list is fetched as the modal opens, as core's own add modal fetches it, so it
            // reflects the configuration of the moment rather than of the page load.
            const blocks = pagehash === null || pagehash === ''
                ? current.availableblocks
                : await fetchAddableBlocks(pagehash);
            await openAddBlockModal({
                blocks,
                title: labels.addblock,
                pagehash,
                returnElement: trigger,
                onSelect(block) {
                    void add(block, destination);
                },
                onFormSubmitted(block) {
                    void placeFormAdded(block, destination);
                },
            });
        } catch (error_) {
            setError(describeError(error_));
        }
    }, [add, labels, pagehash, placeFormAdded]);

    /**
     * Discard the user's own dashboard customisation and revert to the site default.
     * Own dashboard only - see the 'reset' case in update_dashboard.php.
     */
    const reset = useCallback(async () => {
        if (!data) {
            return;
        }

        setSaving(true);
        try {
            await updateDashboard('reset', false);
            // Reset replaces the user's page with a fresh copy of the site default. Reload the
            // document so the server-rendered page chrome is set up for that new page; Edit mode
            // stays on across the reload, so the user can carry on arranging it.
            globalThis.location.reload();
        } catch (error_) {
            setError(describeError(error_));
        } finally {
            setSaving(false);
        }
    }, [data]);

    if (!data || !labels) {
        return <DashboardPlaceholder error={error} loadingLabel={loadingLabel} initialLayout={initialLayout} />;
    }

    // One spare row while a block moves, so it can be put below everything else.
    const rows = gridRows(previewLayout, interaction);
    const prospective = prospectiveFor(interaction);
    // A block is being moved: every grid unit shows as an active cell.
    const moving = Boolean(interaction);
    const requestReset = () => {
        setConfirmAction({type: 'reset'});
    };

    return <div className='core-my-dashboard-app' aria-busy={saving}>
        {error !== '' && <div className='alert alert-danger' role='alert'>{error}</div>}
        <div aria-live='polite' aria-atomic='true'>
            <div key={announcement.serial} className='visually-hidden'>{announcement.text}</div>
        </div>
        {/*
            The "Add a block" control is the same button core/add_block_button renders above every
            other block region (same markup, classes and wrapper), in the same place, so it reads,
            and is found by the standard Behat steps, exactly as on any other page. It deliberately
            carries no data-key="addblock": that is core_block/add_modal's hook, which is not loaded
            on the dashboard pages, and the grid opens the modal itself (see openAddBlock) so that
            the chosen block is placed, below everything else, without a page reload. "Reset page to
            default" is the page's own header button, as on the classic dashboard (my/index.php).
        */}
        {data.editing && <div className='d-grid core-my-dashboard-addblock' data-region='add-block-button'>
            <a
                href={addBlockFallbackUrl()}
                className='btn btn-add-content mb-3'
                onClick={event => {
                    event.preventDefault();
                    void openAddBlock({position: 'end'}, event.currentTarget);
                }}
            >
                <Icon markup={data.icons.add} className='py-2 me-2' />{labels.addblock}
            </a>
        </div>}
        <DashboardErrorBoundary
            resetLabel={labels.reset}
            onReset={!siteDefault && data.canedit ? requestReset : undefined}
        >
            {/* The heading the classic content block region has: block titles are level three
                headings below it, so the headings inside a block keep the level they were written
                for. */}
            <h2 className='visually-hidden'>{labels.blocks}</h2>
            <div
                ref={gridReference}
                // Also a block region by class, which is how selectors written for the classic
                // block regions find a block on a page (a user tour step that targets a block).
                className='core-my-dashboard-grid block-region'
                style={{
                    gridTemplateColumns: `repeat(${columnCount}, minmax(0, 1fr))`,
                    gridTemplateRows: `repeat(${rows}, ${ROW_HEIGHT}px)`,
                }}
                data-columns={columnCount}
            >
                {data.editing && <BoardCells
                    rows={rows}
                    columnCount={columnCount}
                    layout={previewLayout}
                    active={moving}
                    interaction={interaction}
                    prospective={prospective}
                    labels={labels}
                    cueIcon={data.icons.add}
                    onActivate={(targetColumn, targetRow) => {
                        void openAddBlock({column: targetColumn, row: targetRow});
                    }}
                    onPlace={place}
                />}
                {previewLayout.map(item => {
                    const block = blocksById.get(item.id);
                    if (!block) {
                        return null;
                    }

                    return <DashboardTile
                        key={item.id}
                        block={block}
                        item={item}
                        labels={labels}
                        icons={data.icons}
                        editing={data.editing}
                        activeMode={interaction?.id === item.id ? interaction.mode : undefined}
                        showControls={interaction?.id === item.id && interaction.origin !== 'pointer'}
                        drag={interaction?.id === item.id ? interaction.drag : undefined}
                        dragOrigin={interaction?.id === item.id ? interaction.original : undefined}
                        shouldAnimatePosition={interaction?.origin === 'pointer' && interaction.id !== item.id}
                        isBumped={bumpedBlockIds.has(item.id)}
                        onStart={start}
                        onKeyDown={keyDown}
                        onPointerDown={pointerDown}
                        onDirection={shift}
                        onCommit={() => {
                            void commit();
                        }}
                    />;
                })}
            </div>
        </DashboardErrorBoundary>
        {confirmAction && <ConfirmationDialog
            title={labels.resetheading}
            message={labels.confirmreset}
            confirmLabel={labels.confirm}
            cancelLabel={labels.cancel}
            onConfirm={() => {
                setConfirmAction(null);
                void reset();
            }}
            onCancel={() => {
                setConfirmAction(null);
            }}
        />}
    </div>;
};

export default Dashboard;
