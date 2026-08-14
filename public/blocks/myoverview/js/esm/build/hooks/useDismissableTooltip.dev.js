var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { useCallback, useEffect, useRef, useState } from "react";
function useDismissableTooltip() {
  const [suppressed, setSuppressed] = useState(false);
  const activeRef = useRef(0);
  const onKeyDown = useCallback((e) => {
    if (e.key === "Escape") {
      setSuppressed(true);
    }
  }, []);
  const enter = useCallback(() => {
    activeRef.current++;
    if (activeRef.current === 1) {
      document.addEventListener("keydown", onKeyDown, true);
    }
  }, [onKeyDown]);
  const leave = useCallback(() => {
    activeRef.current = Math.max(0, activeRef.current - 1);
    if (activeRef.current === 0) {
      document.removeEventListener("keydown", onKeyDown, true);
      setSuppressed(false);
    }
  }, [onKeyDown]);
  useEffect(() => () => document.removeEventListener("keydown", onKeyDown, true), [onKeyDown]);
  return {
    tooltipAttr: /* @__PURE__ */ __name((text) => suppressed ? void 0 : text, "tooltipAttr"),
    tooltipTriggerProps: {
      onMouseEnter: enter,
      onMouseLeave: leave,
      onFocus: enter,
      onBlur: leave
    }
  };
}
__name(useDismissableTooltip, "useDismissableTooltip");
export {
  useDismissableTooltip
};
//# sourceMappingURL=useDismissableTooltip.dev.js.map
