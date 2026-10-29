var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
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
  useState
} from "react";
import { Button } from "@moodlehq/design-system";
import DashboardTile from "./components/DashboardTile";
import ConfirmationDialog from "./components/ConfirmationDialog";
import Icon from "./components/Icon";
import GridCell from "./components/GridCell";
import DashboardLoading from "./components/DashboardLoading";
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
  writeBack
} from "./layout";
import {
  fetchAddableBlocks,
  getDashboard,
  updateDashboard
} from "./repository";
import { useDashboardLabels } from "./strings";
import { addBlockFallbackUrl, openAddBlockModal } from "./addBlockModal";
import Pending from "@moodle/lms/core/pending";
import { requireManyAsync } from "@moodle/lms/core/amd";
import { isMoodleAjaxError } from "@moodle/lms/core/ajax";
import { getString } from "@moodle/lms/core/stringUtils";
class DashboardErrorBoundary extends React.Component {
  static {
    __name(this, "DashboardErrorBoundary");
  }
  static getDerivedStateFromError(error) {
    return { error };
  }
  state = { error: null };
  componentDidCatch(error) {
    console.error("[core_my/dashboard] Rendering failed:", error);
  }
  render() {
    if (!this.state.error) {
      return this.props.children;
    }
    return /* @__PURE__ */ jsxDEV("div", { className: "core-my-dashboard-status alert alert-danger", role: "alert", children: [
      /* @__PURE__ */ jsxDEV("p", { children: this.state.error.message }, void 0, false, {
        fileName: "public/my/js/esm/src/index.tsx",
        lineNumber: 92,
        columnNumber: 13
      }, this),
      this.props.onReset && this.props.resetLabel !== void 0 && this.props.resetLabel !== "" && /* @__PURE__ */ jsxDEV(
        Button,
        {
          variant: "outline-danger",
          label: this.props.resetLabel,
          onClick: this.props.onReset
        },
        void 0,
        false,
        {
          fileName: "public/my/js/esm/src/index.tsx",
          lineNumber: 93,
          columnNumber: 107
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 91,
      columnNumber: 16
    }, this);
  }
}
const BoardCells = /* @__PURE__ */ __name(({
  rows,
  columnCount,
  layout,
  active,
  interaction,
  prospective,
  labels,
  cueIcon,
  onActivate,
  onPlace
}) => {
  if (!active) {
    return /* @__PURE__ */ jsxDEV(Fragment, { children: emptyRegions(layout, columnCount, rows).map((region) => /* @__PURE__ */ jsxDEV(
      GridCell,
      {
        column: region.column,
        row: region.row,
        columns: region.columns,
        rows: region.rows,
        label: labels.addblockhere,
        cueLabel: labels.addblockhere,
        cueIcon,
        onActivate
      },
      `${region.column}:${region.row}`,
      false,
      {
        fileName: "public/my/js/esm/src/index.tsx",
        lineNumber: 151,
        columnNumber: 68
      }
    )) }, void 0, false, {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 150,
      columnNumber: 16
    });
  }
  const placing = interaction?.origin === "mouseclick";
  const footprint = prospective;
  const distance = /* @__PURE__ */ __name((column, row) => {
    if (!footprint) {
      return void 0;
    }
    const across = Math.max(footprint.column - column, column - (footprint.column + footprint.columns - 1), 0);
    const down = Math.max(footprint.row - row, row - (footprint.row + footprint.rows - 1), 0);
    return Math.max(across, down);
  }, "distance");
  return /* @__PURE__ */ jsxDEV(Fragment, { children: Array.from({ length: rows }, (_, row) => Array.from({ length: columnCount }, (__, column) => /* @__PURE__ */ jsxDEV(
    GridCell,
    {
      column,
      row,
      state: "active",
      previewDistance: distance(column, row),
      onPlace: placing ? onPlace : void 0
    },
    `${column}:${row}`,
    false,
    {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 183,
      columnNumber: 99
    }
  ))) }, void 0, false, {
    fileName: "public/my/js/esm/src/index.tsx",
    lineNumber: 182,
    columnNumber: 12
  });
}, "BoardCells");
const DASHBOARD_SETTLE_QUIET_MS = 250;
const DASHBOARD_SETTLE_TIMEOUT_MS = 5e3;
let dashboardSettleCounter = 0;
const resolveSiteDefault = /* @__PURE__ */ __name((property) => property ?? globalThis.location.pathname.endsWith("/my/indexsys.php"), "resolveSiteDefault");
const resolveLimits = /* @__PURE__ */ __name((data) => data?.limits ?? DEFAULT_LIMITS, "resolveLimits");
const DashboardPlaceholder = /* @__PURE__ */ __name(({ error, loadingLabel, initialLayout }) => error === "" ? /* @__PURE__ */ jsxDEV(DashboardLoading, { label: loadingLabel, layout: initialLayout }, void 0, false, {
  fileName: "public/my/js/esm/src/index.tsx",
  lineNumber: 238,
  columnNumber: 7
}) : /* @__PURE__ */ jsxDEV("div", { className: "core-my-dashboard-status alert alert-danger", role: "alert", children: error }, void 0, false, {
  fileName: "public/my/js/esm/src/index.tsx",
  lineNumber: 239,
  columnNumber: 7
}), "DashboardPlaceholder");
const describeError = /* @__PURE__ */ __name((caught) => {
  if (caught instanceof Error || isMoodleAjaxError(caught)) {
    return caught.message;
  }
  return String(caught);
}, "describeError");
const prospectiveFor = /* @__PURE__ */ __name((interaction) => interaction?.origin === "pointer" ? interaction.draft : void 0, "prospectiveFor");
const gridRows = /* @__PURE__ */ __name((layout, interaction) => Math.max(1, maxRow(layout)) + (interaction?.mode === "move" && interaction.origin !== "keyboard" ? 1 : 0), "gridRows");
const isRtl = /* @__PURE__ */ __name((element) => Boolean(element) && getComputedStyle(element).direction === "rtl", "isRtl");
const layoutChanged = /* @__PURE__ */ __name((original, draft) => original.column !== draft.column || original.row !== draft.row || original.columns !== draft.columns || original.rows !== draft.rows, "layoutChanged");
const useCollectedBlockJavascript = /* @__PURE__ */ __name((data, gridReference) => {
  useEffect(() => {
    if (!data || data.javascript === "") {
      return void 0;
    }
    let superseded = false;
    let settled = false;
    let quietTimer;
    let observer;
    const pending = new Pending(`core_my/dashboard:settling:${dashboardSettleCounter++}`);
    const settle = /* @__PURE__ */ __name(() => {
      if (settled) {
        return;
      }
      settled = true;
      clearTimeout(quietTimer);
      clearTimeout(hardTimer);
      observer?.disconnect();
      pending.resolve();
    }, "settle");
    const hardTimer = setTimeout(settle, DASHBOARD_SETTLE_TIMEOUT_MS);
    const grid = gridReference.current;
    if (grid) {
      observer = new MutationObserver(() => {
        clearTimeout(quietTimer);
        quietTimer = setTimeout(settle, DASHBOARD_SETTLE_QUIET_MS);
      });
      observer.observe(grid, { childList: true, subtree: true });
    }
    quietTimer = setTimeout(settle, DASHBOARD_SETTLE_QUIET_MS);
    void requireManyAsync(["core/fragment", "core/templates"]).then(([fragment, templates]) => {
      if (superseded) {
        return void 0;
      }
      const processed = fragment.processCollectedJavascript(data.javascript);
      templates.runTemplateJS(processed);
      return void 0;
    });
    return () => {
      superseded = true;
      settle();
    };
  }, [data]);
}, "useCollectedBlockJavascript");
const useResponsiveColumnCount = /* @__PURE__ */ __name((gridReference, mounted, data) => {
  const [columnCount, setColumnCount] = useState(1);
  const columnCountReference = useRef(1);
  useLayoutEffect(() => {
    const grid = gridReference.current;
    if (!mounted || !grid) {
      return void 0;
    }
    const measure = /* @__PURE__ */ __name(() => {
      const next = columnsForWidth(grid.getBoundingClientRect().width);
      columnCountReference.current = next;
      setColumnCount(next);
    }, "measure");
    const observer = new ResizeObserver(measure);
    observer.observe(grid);
    measure();
    return () => {
      observer.disconnect();
    };
  }, [mounted, data]);
  return [columnCount, columnCountReference];
}, "useResponsiveColumnCount");
const Dashboard = /* @__PURE__ */ __name(({
  loadingLabel = "",
  initialLayout = [],
  siteDefault: siteDefaultProperty,
  pagehash = null
}) => {
  const [data, setData] = useState(null);
  const [canonical, setCanonical] = useState([]);
  const [interaction, setInteraction] = useState(null);
  const [announcement, setAnnouncement] = useState({ text: "", serial: 0 });
  const [error, setError] = useState("");
  const [confirmAction, setConfirmAction] = useState(null);
  const [saving, setSaving] = useState(false);
  const gridReference = useRef(null);
  const pointerReference = useRef(null);
  const interactionReference = useRef(null);
  const canonicalReference = useRef([]);
  const displayLayoutReference = useRef([]);
  const dataReference = useRef(null);
  const siteDefault = resolveSiteDefault(siteDefaultProperty);
  const labels = useDashboardLabels();
  const limits = resolveLimits(data);
  const limitsReference = useRef(limits);
  limitsReference.current = limits;
  const load = useCallback(async () => {
    const pending = new Pending("core_my/dashboard:load");
    try {
      const response = await getDashboard(siteDefault);
      setData(response);
      dataReference.current = response;
      setCanonical(response.layout);
      canonicalReference.current = response.layout;
      setError("");
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
  useCollectedBlockJavascript(labels ? data : null, gridReference);
  const [columnCount, columnCountReference] = useResponsiveColumnCount(gridReference, Boolean(data && labels), data);
  const displayLayout = useMemo(() => packLayout(canonical, columnCount), [canonical, columnCount]);
  displayLayoutReference.current = displayLayout;
  const previewLayout = useMemo(() => interaction ? packWithPinned(displayLayout, columnCount, interaction.draft, limits) : displayLayout, [columnCount, displayLayout, interaction, limits]);
  const bumpedBlockIds = useMemo(() => {
    if (interaction?.origin !== "pointer") {
      return /* @__PURE__ */ new Set();
    }
    const originalItems = new Map(displayLayout.map((item) => [item.id, item]));
    return new Set(previewLayout.filter((item) => {
      const original = originalItems.get(item.id);
      return item.id !== interaction.id && original && (item.column !== original.column || item.row !== original.row);
    }).map((item) => item.id));
  }, [displayLayout, interaction, previewLayout]);
  const blocksById = useMemo(() => new Map((data?.blocks ?? []).map((block) => [block.id, block])), [data]);
  const announce = useCallback(async (key, value) => {
    const text = await getString(key, "my", value);
    setAnnouncement((previous) => ({ text, serial: previous.serial + 1 }));
  }, []);
  const start = useCallback((id, mode, origin = "keyboard") => {
    const item = displayLayout.find((candidate) => candidate.id === id);
    const block = blocksById.get(id);
    if (!item || !block) {
      return;
    }
    const next = {
      id,
      mode,
      origin,
      original: item,
      draft: item,
      before: displayLayout
    };
    interactionReference.current = next;
    setInteraction(next);
    void announce(mode === "move" ? "dashboardmovebegin" : "dashboardresizebegin", block.title);
  }, [announce, blocksById, displayLayout]);
  const shift = useCallback((visualHorizontal, vertical) => {
    const { current } = interactionReference;
    if (!current) {
      return;
    }
    const horizontal = isRtl(gridReference.current) ? -visualHorizontal : visualHorizontal;
    const bounds = limitsReference.current;
    let draft = { ...current.draft };
    if (current.mode === "move") {
      draft.column += horizontal;
      draft.row += vertical;
    } else {
      const nextColumns = draft.columns + horizontal;
      const nextRows = draft.rows + vertical;
      if (nextColumns < bounds.mincolumns || nextRows < bounds.minrows) {
        void announce("dashboardminimumsize", `${bounds.mincolumns} \xD7 ${bounds.minrows}`);
        return;
      }
      draft.columns = nextColumns;
      draft.rows = nextRows;
    }
    draft = clampToGrid(draft, columnCount, bounds);
    if (!layoutChanged(current.draft, draft)) {
      if (current.mode === "move") {
        void announce("gridcellposition", { row: draft.row + 1, column: draft.column + 1 });
      }
      return;
    }
    const next = { ...current, draft };
    interactionReference.current = next;
    setInteraction(next);
    if (current.mode === "move") {
      void announce("gridcellposition", { row: draft.row + 1, column: draft.column + 1 });
    } else {
      void announce("dashboardresizesize", { columns: draft.columns, rows: draft.rows });
    }
  }, [announce, columnCount]);
  useEffect(() => {
    if (interaction?.origin !== "keyboard") {
      return;
    }
    const selector = `.core-my-dashboard-tile[data-block-id="${CSS.escape(String(interaction.id))}"]`;
    gridReference.current?.querySelector(selector)?.scrollIntoView?.({ block: "nearest", inline: "nearest" });
  }, [interaction?.origin, interaction?.id, interaction?.draft]);
  const commit = useCallback(async () => {
    const { current } = interactionReference;
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
      await updateDashboard("save", siteDefault, next);
      setCanonical(next);
      canonicalReference.current = next;
      const item = derived.find((candidate) => candidate.id === current.id);
      await announce(current.mode === "move" ? "dashboardmovecommitted" : "dashboardresizecommitted", {
        row: item.row + 1,
        column: item.column + 1,
        columns: item.columns,
        rows: item.rows,
        disturbed
      });
      interactionReference.current = null;
      setInteraction(null);
    } catch (error_) {
      setError(describeError(error_));
    } finally {
      setSaving(false);
    }
  }, [announce, siteDefault]);
  const cancel = useCallback(() => {
    if (interaction) {
      void announce("dashboardoperationdiscarded");
    }
    interactionReference.current = null;
    setInteraction(null);
  }, [announce, interaction]);
  const target = useCallback((column, row) => {
    const { current } = interactionReference;
    if (!current) {
      return null;
    }
    const draft = clampToGrid(current.mode === "move" ? { ...current.draft, column, row } : {
      ...current.draft,
      columns: column - current.original.column + 1,
      rows: row - current.original.row + 1
    }, columnCount, limitsReference.current);
    if (!layoutChanged(current.draft, draft)) {
      return current;
    }
    const next = { ...current, draft };
    interactionReference.current = next;
    setInteraction(next);
    return next;
  }, [columnCount]);
  const place = useCallback((column, row) => {
    if (target(column, row)) {
      void commit();
    }
  }, [commit, target]);
  const keyDown = useCallback((event, id, mode) => {
    if (!interaction && (event.key === " " || event.key === "Enter")) {
      event.preventDefault();
      start(id, mode);
      return;
    }
    if (interaction?.id !== id || interaction.mode !== mode) {
      return;
    }
    if (event.key === "Escape") {
      event.preventDefault();
      cancel();
    } else if (event.key === " " || event.key === "Enter") {
      event.preventDefault();
      void commit();
    } else if (event.key.startsWith("Arrow")) {
      event.preventDefault();
      const directions = {
        ArrowLeft: [-1, 0],
        ArrowRight: [1, 0],
        ArrowUp: [0, -1],
        ArrowDown: [0, 1]
      };
      shift(...directions[event.key]);
    }
  }, [cancel, commit, interaction, shift, start]);
  const pointerDown = useCallback((event, id, mode) => {
    event.preventDefault();
    start(id, mode, "pointer");
    pointerReference.current = { x: event.clientX, y: event.clientY, moved: false };
    const origin = displayLayout.find((item) => item.id === id);
    if (!origin) {
      return;
    }
    const bounds = limitsReference.current;
    const grid = gridReference.current;
    const cellWidth = grid ? (grid.getBoundingClientRect().width - GRID_GAP * (columnCount - 1)) / columnCount : 1;
    const columnStride = cellWidth + GRID_GAP;
    const rowStride = ROW_HEIGHT + GRID_GAP;
    const originalWidth = origin.columns * cellWidth + (origin.columns - 1) * GRID_GAP;
    const originalHeight = origin.rows * ROW_HEIGHT + (origin.rows - 1) * GRID_GAP;
    const move = /* @__PURE__ */ __name((pointerEvent) => {
      const pointer = pointerReference.current;
      if (!pointer) {
        return;
      }
      const deltaX = pointerEvent.clientX - pointer.x;
      const deltaY = pointerEvent.clientY - pointer.y;
      pointer.moved = pointer.moved || Math.abs(deltaX) >= 4 || Math.abs(deltaY) >= 4;
      const horizontal = Math.round(deltaX / columnStride);
      const vertical = Math.round(deltaY / rowStride);
      setInteraction((current) => {
        if (!current) {
          return current;
        }
        let draft = { ...origin };
        let drag;
        if (mode === "move") {
          draft.column = origin.column + horizontal;
          draft.row = origin.row + vertical;
          drag = {
            x: Math.max(
              -origin.column * columnStride,
              Math.min((columnCount - origin.column - origin.columns) * columnStride, deltaX)
            ),
            y: Math.max(
              -origin.row * rowStride,
              Math.min((bounds.maxrows - origin.row - origin.rows) * rowStride, deltaY)
            )
          };
        } else {
          draft.columns = origin.columns + horizontal;
          draft.rows = origin.rows + vertical;
          drag = {
            x: 0,
            y: 0,
            width: Math.max(cellWidth, Math.min(
              (columnCount - origin.column) * columnStride - GRID_GAP,
              originalWidth + deltaX
            )),
            height: Math.max(ROW_HEIGHT, Math.min(
              (bounds.maxrows - origin.row) * rowStride - GRID_GAP,
              originalHeight + deltaY
            )),
            shrinking: deltaX < 0 || deltaY < 0
          };
        }
        draft = clampToGrid(draft, columnCount, bounds);
        const next = { ...current, draft, drag };
        interactionReference.current = next;
        return next;
      });
    }, "move");
    const cleanup = /* @__PURE__ */ __name(() => {
      globalThis.removeEventListener("pointermove", move);
      globalThis.removeEventListener("pointerup", up);
      globalThis.removeEventListener("pointercancel", abort);
      pointerReference.current = null;
    }, "cleanup");
    const up = /* @__PURE__ */ __name(() => {
      const { current } = interactionReference;
      if (pointerReference.current?.moved && current && layoutChanged(current.original, current.draft)) {
        void commit();
      } else if (pointerReference.current?.moved) {
        cancel();
      } else {
        setInteraction((previous) => {
          if (!previous) {
            return previous;
          }
          const next = {
            ...previous,
            origin: "mouseclick",
            drag: void 0,
            draft: previous.original
          };
          interactionReference.current = next;
          return next;
        });
      }
      cleanup();
    }, "up");
    const abort = /* @__PURE__ */ __name(() => {
      cleanup();
      cancel();
    }, "abort");
    globalThis.addEventListener("pointermove", move);
    globalThis.addEventListener("pointerup", up);
    globalThis.addEventListener("pointercancel", abort);
  }, [cancel, columnCount, commit, displayLayout, start]);
  const placeNewBlock = useCallback(async (blockid, destination, title) => {
    const previousIds = new Set((dataReference.current?.blocks ?? []).map((block) => block.id));
    const response = await load();
    if (!response) {
      return;
    }
    const bounds = response.limits;
    const currentColumns = columnCountReference.current;
    const item = blockid === null ? response.layout.filter((candidate) => !previousIds.has(candidate.id)).sort((left, right) => right.id - left.id)[0] : response.layout.find((candidate) => candidate.id === blockid);
    if (item) {
      const display = packLayout(response.layout, currentColumns);
      const others = display.filter((candidate) => candidate.id !== item.id);
      let pinned;
      if (destination.column !== void 0 && destination.row !== void 0) {
        const wanted = clampToGrid({ ...item, column: destination.column, row: destination.row }, currentColumns, bounds);
        const fitted = fitToFreeSpace(others, currentColumns, wanted, bounds);
        pinned = fitted ? { ...item, ...fitted } : wanted;
      } else {
        pinned = { ...item, column: 0, row: maxRow(others) };
      }
      const derived = packWithPinned(display, currentColumns, pinned, bounds);
      const next = writeBack(response.layout, derived, item.id, bounds, currentColumns);
      await updateDashboard("save", siteDefault, next);
      setCanonical(next);
      canonicalReference.current = next;
    }
    await announce("dashboardblockadded", title);
  }, [announce, columnCountReference, load, siteDefault]);
  const add = useCallback(async (block, destination) => {
    setSaving(true);
    try {
      const result = await updateDashboard("add", siteDefault, [], block.name);
      await placeNewBlock(result.blockid > 0 ? result.blockid : null, destination, block.title);
    } catch (error_) {
      setError(describeError(error_));
    } finally {
      setSaving(false);
    }
  }, [placeNewBlock, siteDefault]);
  const placeFormAdded = useCallback(async (block, destination) => {
    setSaving(true);
    try {
      await placeNewBlock(null, destination, block.title);
    } catch (error_) {
      setError(describeError(error_));
    } finally {
      setSaving(false);
    }
  }, [placeNewBlock]);
  const openAddBlock = useCallback(async (destination, trigger) => {
    const { current } = dataReference;
    if (!current || !labels) {
      return;
    }
    try {
      const blocks = pagehash === null || pagehash === "" ? current.availableblocks : await fetchAddableBlocks(pagehash);
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
        }
      });
    } catch (error_) {
      setError(describeError(error_));
    }
  }, [add, labels, pagehash, placeFormAdded]);
  const reset = useCallback(async () => {
    if (!data) {
      return;
    }
    setSaving(true);
    try {
      await updateDashboard("reset", false);
      globalThis.location.reload();
    } catch (error_) {
      setError(describeError(error_));
    } finally {
      setSaving(false);
    }
  }, [data]);
  if (!data || !labels) {
    return /* @__PURE__ */ jsxDEV(DashboardPlaceholder, { error, loadingLabel, initialLayout }, void 0, false, {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 1015,
      columnNumber: 16
    });
  }
  const rows = gridRows(previewLayout, interaction);
  const prospective = prospectiveFor(interaction);
  const moving = Boolean(interaction);
  const requestReset = /* @__PURE__ */ __name(() => {
    setConfirmAction({ type: "reset" });
  }, "requestReset");
  return /* @__PURE__ */ jsxDEV("div", { className: "core-my-dashboard-app", "aria-busy": saving, children: [
    error !== "" && /* @__PURE__ */ jsxDEV("div", { className: "alert alert-danger", role: "alert", children: error }, void 0, false, {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 1028,
      columnNumber: 26
    }),
    /* @__PURE__ */ jsxDEV("div", { "aria-live": "polite", "aria-atomic": "true", children: /* @__PURE__ */ jsxDEV("div", { className: "visually-hidden", children: announcement.text }, announcement.serial, false, {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 1030,
      columnNumber: 13
    }) }, void 0, false, {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 1029,
      columnNumber: 9
    }),
    data.editing && /* @__PURE__ */ jsxDEV("div", { className: "d-grid core-my-dashboard-addblock", "data-region": "add-block-button", children: /* @__PURE__ */ jsxDEV(
      "a",
      {
        href: addBlockFallbackUrl(),
        className: "btn btn-add-content mb-3",
        onClick: (event) => {
          event.preventDefault();
          void openAddBlock({ position: "end" }, event.currentTarget);
        },
        children: [
          /* @__PURE__ */ jsxDEV(Icon, { markup: data.icons.add, className: "py-2 me-2" }, void 0, false, {
            fileName: "public/my/js/esm/src/index.tsx",
            lineNumber: 1050,
            columnNumber: 17
          }),
          labels.addblock
        ]
      },
      void 0,
      true,
      {
        fileName: "public/my/js/esm/src/index.tsx",
        lineNumber: 1042,
        columnNumber: 13
      }
    ) }, void 0, false, {
      fileName: "public/my/js/esm/src/index.tsx",
      lineNumber: 1041,
      columnNumber: 26
    }),
    /* @__PURE__ */ jsxDEV(
      DashboardErrorBoundary,
      {
        resetLabel: labels.reset,
        onReset: !siteDefault && data.canedit ? requestReset : void 0,
        children: [
          /* @__PURE__ */ jsxDEV("h2", { className: "visually-hidden", children: labels.blocks }, void 0, false, {
            fileName: "public/my/js/esm/src/index.tsx",
            lineNumber: 1060,
            columnNumber: 13
          }),
          /* @__PURE__ */ jsxDEV(
            "div",
            {
              ref: gridReference,
              className: "core-my-dashboard-grid block-region",
              style: {
                gridTemplateColumns: `repeat(${columnCount}, minmax(0, 1fr))`,
                gridTemplateRows: `repeat(${rows}, ${ROW_HEIGHT}px)`
              },
              "data-columns": columnCount,
              children: [
                data.editing && /* @__PURE__ */ jsxDEV(
                  BoardCells,
                  {
                    rows,
                    columnCount,
                    layout: previewLayout,
                    active: moving,
                    interaction,
                    prospective,
                    labels,
                    cueIcon: data.icons.add,
                    onActivate: (targetColumn, targetRow) => {
                      void openAddBlock({ column: targetColumn, row: targetRow });
                    },
                    onPlace: place
                  },
                  void 0,
                  false,
                  {
                    fileName: "public/my/js/esm/src/index.tsx",
                    lineNumber: 1072,
                    columnNumber: 34
                  }
                ),
                previewLayout.map((item) => {
                  const block = blocksById.get(item.id);
                  if (!block) {
                    return null;
                  }
                  return /* @__PURE__ */ jsxDEV(
                    DashboardTile,
                    {
                      block,
                      item,
                      labels,
                      icons: data.icons,
                      editing: data.editing,
                      activeMode: interaction?.id === item.id ? interaction.mode : void 0,
                      showControls: interaction?.id === item.id && interaction.origin !== "pointer",
                      drag: interaction?.id === item.id ? interaction.drag : void 0,
                      dragOrigin: interaction?.id === item.id ? interaction.original : void 0,
                      shouldAnimatePosition: interaction?.origin === "pointer" && interaction.id !== item.id,
                      isBumped: bumpedBlockIds.has(item.id),
                      onStart: start,
                      onKeyDown: keyDown,
                      onPointerDown: pointerDown,
                      onDirection: shift,
                      onCommit: () => {
                        void commit();
                      }
                    },
                    item.id,
                    false,
                    {
                      fileName: "public/my/js/esm/src/index.tsx",
                      lineNumber: 1092,
                      columnNumber: 28
                    }
                  );
                })
              ]
            },
            void 0,
            true,
            {
              fileName: "public/my/js/esm/src/index.tsx",
              lineNumber: 1061,
              columnNumber: 13
            }
          )
        ]
      },
      void 0,
      true,
      {
        fileName: "public/my/js/esm/src/index.tsx",
        lineNumber: 1053,
        columnNumber: 9
      }
    ),
    confirmAction && /* @__PURE__ */ jsxDEV(
      ConfirmationDialog,
      {
        title: labels.resetheading,
        message: labels.confirmreset,
        confirmLabel: labels.confirm,
        cancelLabel: labels.cancel,
        onConfirm: () => {
          setConfirmAction(null);
          void reset();
        },
        onCancel: () => {
          setConfirmAction(null);
        }
      },
      void 0,
      false,
      {
        fileName: "public/my/js/esm/src/index.tsx",
        lineNumber: 1116,
        columnNumber: 27
      }
    )
  ] }, void 0, true, {
    fileName: "public/my/js/esm/src/index.tsx",
    lineNumber: 1027,
    columnNumber: 12
  });
}, "Dashboard");
var index_default = Dashboard;
export {
  DashboardErrorBoundary,
  index_default as default
};
//# sourceMappingURL=index.dev.js.map
