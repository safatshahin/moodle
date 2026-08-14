var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
import { useEffect, useState } from "react";
import { resolveZeroStateCopy } from "../strings";
import { useStrings } from "../state";
function EmptyState({ zerostate, variant, illustrationurl }) {
  const strings = useStrings();
  const [copyState, setCopyState] = useState(null);
  useEffect(() => {
    if (!zerostate) {
      return void 0;
    }
    let cancelled = false;
    resolveZeroStateCopy(zerostate).then((copy) => {
      if (!cancelled) {
        setCopyState(copy);
      }
      return null;
    }).catch((error) => {
      if (!cancelled) {
        setCopyState(null);
      }
      window.console.error("[block_myoverview] Failed to resolve zero-state copy", error);
    });
    return () => {
      cancelled = true;
    };
  }, [zerostate]);
  const illustration = /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty__illustration", "aria-hidden": "true", children: /* @__PURE__ */ jsxDEV("img", { src: illustrationurl, alt: "" }, void 0, false, {
    fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
    lineNumber: 88,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
    lineNumber: 87,
    columnNumber: 9
  }, this);
  if (zerostate) {
    const managelabel = zerostate.sitehascourses ? strings.managecourses : strings.managecategories;
    return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty", "data-variant": "zerostate", children: [
      illustration,
      copyState && /* @__PURE__ */ jsxDEV(Fragment, { children: [
        /* @__PURE__ */ jsxDEV("h2", { className: "courseoverview-empty__title", children: copyState.title }, void 0, false, {
          fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
          lineNumber: 102,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV(
          "p",
          {
            className: "courseoverview-empty__text",
            dangerouslySetInnerHTML: { __html: copyState.intro }
          },
          void 0,
          false,
          {
            fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
            lineNumber: 103,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 98,
        columnNumber: 21
      }, this),
      zerostate.variant === "create" && /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty__actions", children: [
        zerostate.manageurl && /* @__PURE__ */ jsxDEV("a", { className: "btn btn-outline-primary", href: zerostate.manageurl, children: managelabel }, void 0, false, {
          fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
          lineNumber: 112,
          columnNumber: 29
        }, this),
        zerostate.createurl && /* @__PURE__ */ jsxDEV("a", { className: "btn btn-primary", href: zerostate.createurl, children: strings.createcourse }, void 0, false, {
          fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
          lineNumber: 117,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 110,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 95,
      columnNumber: 13
    }, this);
  }
  if (variant === "no-results") {
    return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty", "data-variant": "no-results", children: [
      illustration,
      /* @__PURE__ */ jsxDEV("h2", { className: "courseoverview-empty__title", children: strings.emptynoresultstitle }, void 0, false, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 133,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "courseoverview-empty__text", children: strings.emptynoresults }, void 0, false, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 134,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 131,
      columnNumber: 13
    }, this);
  }
  return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty", "data-variant": "all-hidden", children: [
    illustration,
    /* @__PURE__ */ jsxDEV("h2", { className: "courseoverview-empty__title", children: strings.emptyallhiddentitle }, void 0, false, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 145,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "courseoverview-empty__text", children: strings.emptyallhiddenintro }, void 0, false, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 146,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
    lineNumber: 143,
    columnNumber: 9
  }, this);
}
__name(EmptyState, "EmptyState");
export {
  EmptyState as default
};
//# sourceMappingURL=EmptyState.dev.js.map
