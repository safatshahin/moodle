var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Resize handle for the course index drawer.
 *
 * Renders a keyboard and pointer operable separator on the inner edge of the
 * course index drawer. The width is applied by setting the
 * --drawer-index-width custom property on the body, which the theme SCSS maps
 * into the effective drawer width at the lg breakpoint and up. The chosen
 * width is persisted as a user preference once per interaction, on drag end
 * or debounced after key presses, never per pointer move.
 *
 * @module     theme_boost/courseindex_resizer
 * @copyright  2026 A K M Safat Shahin <safatshahin@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {
  useCallback,
  useEffect,
  useRef,
  useState
} from "react";
import Fetch from "@moodle/lms/core/fetch";
import Pending from "@moodle/lms/core/pending";
const WIDTH_PROPERTY = "--drawer-index-width";
const KEY_STEP = 24;
const KEY_STEP_LARGE = 48;
const PERSIST_DEBOUNCE_MS = 400;
function CourseindexResizer({
  minwidth,
  maxwidth,
  preference,
  label,
  drawerid
}) {
  const handleReference = useRef(null);
  const dragState = useRef(null);
  const persistTimer = useRef(void 0);
  const debouncePending = useRef(null);
  const clamp = useCallback(
    (value) => Math.min(maxwidth, Math.max(minwidth, Math.round(value))),
    [minwidth, maxwidth]
  );
  const measureDrawerWidth = useCallback(() => {
    const drawer = document.querySelector(`#${CSS.escape(drawerid)}`);
    const measured = drawer ? Math.round(drawer.getBoundingClientRect().width) : 0;
    return measured > 0 ? clamp(measured) : null;
  }, [drawerid, clamp]);
  const readAppliedWidth = useCallback(() => {
    const raw = getComputedStyle(document.body).getPropertyValue(WIDTH_PROPERTY).trim();
    const saved = raw === "" ? NaN : Number(raw.replace(/px$/u, ""));
    if (Number.isFinite(saved)) {
      return clamp(saved);
    }
    return measureDrawerWidth() ?? minwidth;
  }, [clamp, measureDrawerWidth, minwidth]);
  const [width, setWidthState] = useState(readAppliedWidth);
  const [dragging, setDragging] = useState(false);
  const syncWidthState = useCallback(() => {
    setWidthState(readAppliedWidth());
  }, [readAppliedWidth]);
  const isRtl = /* @__PURE__ */ __name(() => handleReference.current !== null && getComputedStyle(handleReference.current).direction === "rtl", "isRtl");
  const applyWidth = useCallback((value) => {
    const clamped = clamp(value);
    document.body.style.setProperty(WIDTH_PROPERTY, `${clamped}px`);
    setWidthState(clamped);
    return clamped;
  }, [clamp]);
  const persistWidth = useCallback((value) => {
    const pending = new Pending("theme_boost/courseindex_resizer:save");
    Fetch.performPost("core_user", `current/preferences/${preference}`, { body: { value } }).catch(() => null).finally(() => {
      pending.resolve();
    });
  }, [preference]);
  useEffect(() => () => {
    document.body.classList.remove("drawer-resizing");
    clearTimeout(persistTimer.current);
    debouncePending.current?.resolve();
    debouncePending.current = null;
  }, []);
  const onPointerDown = /* @__PURE__ */ __name((event) => {
    if (event.pointerType === "mouse" && event.button !== 0) {
      return;
    }
    dragState.current = {
      pointerId: event.pointerId,
      startX: event.clientX,
      startWidth: readAppliedWidth(),
      moved: false
    };
    setDragging(true);
    document.body.classList.add("drawer-resizing");
    event.currentTarget.setPointerCapture(event.pointerId);
    event.preventDefault();
    event.currentTarget.focus();
  }, "onPointerDown");
  const onPointerMove = /* @__PURE__ */ __name((event) => {
    if (dragState.current?.pointerId !== event.pointerId) {
      return;
    }
    const delta = event.clientX - dragState.current.startX;
    if (Math.abs(delta) > 2) {
      dragState.current.moved = true;
    }
    applyWidth(dragState.current.startWidth + (isRtl() ? -delta : delta));
  }, "onPointerMove");
  const onPointerEnd = /* @__PURE__ */ __name((event) => {
    if (dragState.current?.pointerId !== event.pointerId) {
      return;
    }
    const { moved } = dragState.current;
    dragState.current = null;
    setDragging(false);
    document.body.classList.remove("drawer-resizing");
    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }
    if (moved) {
      persistWidth(readAppliedWidth());
    }
  }, "onPointerEnd");
  const onKeyDown = /* @__PURE__ */ __name((event) => {
    const step = event.shiftKey ? KEY_STEP_LARGE : KEY_STEP;
    const direction = isRtl() ? -1 : 1;
    const current = readAppliedWidth();
    let next;
    switch (event.key) {
      case "ArrowRight": {
        next = current + step * direction;
        break;
      }
      case "ArrowLeft": {
        next = current - step * direction;
        break;
      }
      case "Home": {
        next = minwidth;
        break;
      }
      case "End": {
        next = maxwidth;
        break;
      }
      default: {
        return;
      }
    }
    event.preventDefault();
    const applied = applyWidth(next);
    clearTimeout(persistTimer.current);
    if (debouncePending.current === null) {
      debouncePending.current = new Pending("theme_boost/courseindex_resizer:debounce");
    }
    persistTimer.current = setTimeout(() => {
      persistWidth(applied);
      debouncePending.current?.resolve();
      debouncePending.current = null;
    }, PERSIST_DEBOUNCE_MS);
  }, "onKeyDown");
  return /* @__PURE__ */ jsxDEV(
    "div",
    {
      ref: handleReference,
      className: `drawerresizehandle${dragging ? " dragging" : ""}`,
      role: "separator",
      tabIndex: 0,
      "aria-orientation": "vertical",
      "aria-label": label,
      "aria-controls": drawerid,
      "aria-valuemin": minwidth,
      "aria-valuemax": maxwidth,
      "aria-valuenow": width,
      onPointerDown,
      onPointerMove,
      onPointerUp: onPointerEnd,
      onPointerCancel: onPointerEnd,
      onLostPointerCapture: onPointerEnd,
      onFocus: syncWidthState,
      onKeyDown
    },
    void 0,
    false,
    {
      fileName: "public/theme/boost/js/esm/src/courseindex_resizer.tsx",
      lineNumber: 256,
      columnNumber: 9
    },
    this
  );
}
__name(CourseindexResizer, "CourseindexResizer");
export {
  CourseindexResizer as default
};
//# sourceMappingURL=courseindex_resizer.dev.js.map
