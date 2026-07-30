var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
import { useEffect, useState } from "react";
import { getString } from "@moodle/lms/core/stringUtils";
import { useStrings } from "../state";
const COMPONENT = "block_myoverview";
async function resolveZeroStateCopy(zerostate) {
  if (zerostate.variant === "request") {
    return {
      title: await getString("zero_request_title", COMPONENT),
      intro: await getString("zero_request_intro_short", COMPONENT)
    };
  }
  if (zerostate.variant === "create") {
    const titlekey = zerostate.sitehascourses ? "zero_default_title" : "zero_nocourses_title";
    let introkey = "zero_default_intro";
    if (!zerostate.sitehascourses) {
      introkey = zerostate.quickstarturl ? "zero_request_intro" : "zero_nocourses_intro";
    }
    const docparams = {
      dochref: zerostate.docsurl,
      doctitle: await getString("documentation"),
      doctarget: zerostate.docstarget
    };
    if (zerostate.quickstarturl) {
      docparams.quickhref = zerostate.quickstarturl;
      docparams.quicktitle = await getString("viewquickstart", COMPONENT);
      docparams.quicktarget = "_blank";
    }
    return {
      title: await getString(titlekey, COMPONENT),
      intro: await getString(introkey, COMPONENT, docparams)
    };
  }
  return {
    title: await getString("zero_default_title", COMPONENT),
    intro: await getString("zero_default_intro", COMPONENT)
  };
}
__name(resolveZeroStateCopy, "resolveZeroStateCopy");
function EmptyState({ zerostate, variant, illustrationurl }) {
  const strings = useStrings();
  const [copyState, setCopyState] = useState(null);
  useEffect(() => {
    if (zerostate) {
      resolveZeroStateCopy(zerostate).then(setCopyState).catch(() => setCopyState(null));
    }
  }, [zerostate]);
  const illustration = /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty__illustration", "aria-hidden": "true", children: /* @__PURE__ */ jsxDEV("img", { src: illustrationurl, alt: "" }, void 0, false, {
    fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
    lineNumber: 116,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
    lineNumber: 115,
    columnNumber: 9
  }, this);
  if (zerostate) {
    const managelabel = zerostate.sitehascourses ? strings.managecourses : strings.managecategories;
    return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty", "data-variant": "zerostate", children: [
      illustration,
      copyState && /* @__PURE__ */ jsxDEV(Fragment, { children: [
        /* @__PURE__ */ jsxDEV("h2", { className: "courseoverview-empty__title", children: copyState.title }, void 0, false, {
          fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
          lineNumber: 130,
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
            lineNumber: 131,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 126,
        columnNumber: 21
      }, this),
      zerostate.variant === "create" && /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty__actions", children: [
        zerostate.manageurl && /* @__PURE__ */ jsxDEV("a", { className: "btn btn-outline-primary", href: zerostate.manageurl, children: managelabel }, void 0, false, {
          fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
          lineNumber: 140,
          columnNumber: 29
        }, this),
        zerostate.createurl && /* @__PURE__ */ jsxDEV("a", { className: "btn btn-primary", href: zerostate.createurl, children: strings.createcourse }, void 0, false, {
          fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
          lineNumber: 145,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 138,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 123,
      columnNumber: 13
    }, this);
  }
  if (variant === "no-results") {
    return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty", "data-variant": "no-results", children: [
      illustration,
      /* @__PURE__ */ jsxDEV("h2", { className: "courseoverview-empty__title", children: strings.emptynoresultstitle }, void 0, false, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 161,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "courseoverview-empty__text", children: strings.emptynoresults }, void 0, false, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 162,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 159,
      columnNumber: 13
    }, this);
  }
  if (variant === "all-hidden") {
    return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty", "data-variant": "all-hidden", children: [
      illustration,
      /* @__PURE__ */ jsxDEV("h2", { className: "courseoverview-empty__title", children: strings.emptyallhiddentitle }, void 0, false, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 174,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { className: "courseoverview-empty__text", children: strings.emptyallhiddenintro }, void 0, false, {
        fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
        lineNumber: 175,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 172,
      columnNumber: 13
    }, this);
  }
  const copy = {
    student: strings.emptystudent,
    educator: strings.emptyeducator,
    "no-results": strings.emptynoresults
  };
  return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-empty", "data-variant": variant ?? "student", children: [
    illustration,
    /* @__PURE__ */ jsxDEV("p", { className: "courseoverview-empty__text", children: copy[variant ?? "student"] }, void 0, false, {
      fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
      lineNumber: 188,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/blocks/myoverview/js/esm/src/components/EmptyState.tsx",
    lineNumber: 186,
    columnNumber: 9
  }, this);
}
__name(EmptyState, "EmptyState");
export {
  EmptyState as default
};
//# sourceMappingURL=EmptyState.dev.js.map
