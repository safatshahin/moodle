var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import { ProgressBar } from "@moodlehq/design-system";
import { useStrings } from "../state";
function ProgressIndicator({ progress, labelVariant = "inline" }) {
  const strings = useStrings();
  const clamped = Math.max(0, Math.min(100, Math.round(progress)));
  const count = strings.percentcomplete.replace("{$a}", String(clamped));
  return /* @__PURE__ */ jsxDEV(
    ProgressBar,
    {
      value: clamped,
      labelVariant,
      title: labelVariant === "title" ? count : void 0,
      count: labelVariant === "inline" ? count : void 0,
      "aria-label": strings.courseprogress,
      className: "courseoverview-progress"
    },
    void 0,
    false,
    {
      fileName: "public/blocks/myoverview/js/esm/src/components/ProgressIndicator.tsx",
      lineNumber: 52,
      columnNumber: 9
    },
    this
  );
}
__name(ProgressIndicator, "ProgressIndicator");
export {
  ProgressIndicator as default
};
//# sourceMappingURL=ProgressIndicator.dev.js.map
