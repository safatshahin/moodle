var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import { Pagination as DSPagination } from "@moodlehq/design-system";
import { useStrings } from "../state";
function Pagination({ page, hasNext, onPage }) {
  const strings = useStrings();
  return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-pagination", children: /* @__PURE__ */ jsxDEV(
    DSPagination,
    {
      variant: "grouped",
      totalPages: hasNext ? page + 1 : page,
      currentPage: page,
      onPageChange: onPage,
      ariaLabel: strings.courseoverview,
      previousPageLabel: strings.previouspage,
      nextPageLabel: strings.nextpage
    },
    void 0,
    false,
    {
      fileName: "public/blocks/myoverview/js/esm/src/components/Pagination.tsx",
      lineNumber: 51,
      columnNumber: 13
    },
    this
  ) }, void 0, false, {
    fileName: "public/blocks/myoverview/js/esm/src/components/Pagination.tsx",
    lineNumber: 50,
    columnNumber: 9
  }, this);
}
__name(Pagination, "Pagination");
export {
  Pagination as default
};
//# sourceMappingURL=Pagination.dev.js.map
