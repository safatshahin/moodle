var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import { useId } from "react";
import { CloseButton } from "@moodlehq/design-system";
import { useStrings } from "../state";
import Icon from "@moodle/lms/block_myoverview/components/Icon";
function SearchInput({ value, onChange }) {
  const strings = useStrings();
  const inputId = useId();
  return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-search", children: [
    /* @__PURE__ */ jsxDEV("label", { htmlFor: inputId, className: "visually-hidden", children: strings.searchcourses }, void 0, false, {
      fileName: "public/blocks/myoverview/js/esm/src/components/SearchInput.tsx",
      lineNumber: 47,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(Icon, { name: "magnifying-glass", className: "courseoverview-search__icon" }, void 0, false, {
      fileName: "public/blocks/myoverview/js/esm/src/components/SearchInput.tsx",
      lineNumber: 48,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(
      "input",
      {
        id: inputId,
        type: "text",
        className: "courseoverview-search__input",
        placeholder: strings.search,
        "aria-label": strings.searchcourses,
        value,
        onChange: (e) => onChange(e.target.value)
      },
      void 0,
      false,
      {
        fileName: "public/blocks/myoverview/js/esm/src/components/SearchInput.tsx",
        lineNumber: 49,
        columnNumber: 13
      },
      this
    ),
    value !== "" && /* @__PURE__ */ jsxDEV(
      CloseButton,
      {
        "aria-label": strings.clearsearch,
        size: "sm",
        className: "courseoverview-search__clear",
        onClick: () => onChange("")
      },
      void 0,
      false,
      {
        fileName: "public/blocks/myoverview/js/esm/src/components/SearchInput.tsx",
        lineNumber: 59,
        columnNumber: 17
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/blocks/myoverview/js/esm/src/components/SearchInput.tsx",
    lineNumber: 46,
    columnNumber: 9
  }, this);
}
__name(SearchInput, "SearchInput");
export {
  SearchInput as default
};
//# sourceMappingURL=SearchInput.dev.js.map
