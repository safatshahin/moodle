var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import { useDismissableMenu } from "../hooks/useDismissableMenu";
import { useCourseCallbacks, useStrings } from "../state";
import Icon from "@moodle/lms/block_myoverview/components/Icon";
function EllipsisMenu({ courseId, courseName, isHidden }) {
  const { toggleHidden } = useCourseCallbacks();
  const strings = useStrings();
  const { open, setOpen, containerRef, triggerRef, menuRef, handleMenuKeyDown } = useDismissableMenu("menuitem");
  const stop = /* @__PURE__ */ __name((e) => {
    e.preventDefault();
    e.stopPropagation();
  }, "stop");
  const actionsLabel = strings.actionsfor.replace("{$a}", courseName);
  return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-menu", ref: containerRef, children: [
    /* @__PURE__ */ jsxDEV(
      "button",
      {
        type: "button",
        ref: triggerRef,
        className: "courseoverview-iconbtn",
        "aria-haspopup": "menu",
        "aria-expanded": open,
        title: strings.courseactions,
        onClick: (e) => {
          stop(e);
          setOpen((v) => !v);
        },
        children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "ellipsis-vertical" }, void 0, false, {
            fileName: "public/blocks/myoverview/js/esm/src/components/EllipsisMenu.tsx",
            lineNumber: 77,
            columnNumber: 17
          }, this),
          /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: actionsLabel }, void 0, false, {
            fileName: "public/blocks/myoverview/js/esm/src/components/EllipsisMenu.tsx",
            lineNumber: 81,
            columnNumber: 17
          }, this)
        ]
      },
      void 0,
      true,
      {
        fileName: "public/blocks/myoverview/js/esm/src/components/EllipsisMenu.tsx",
        lineNumber: 65,
        columnNumber: 13
      },
      this
    ),
    open && /* @__PURE__ */ jsxDEV(
      "div",
      {
        className: "courseoverview-menu__list",
        role: "menu",
        "aria-label": actionsLabel,
        ref: menuRef,
        onKeyDown: handleMenuKeyDown,
        children: /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            role: "menuitem",
            className: "courseoverview-menu__item",
            onClick: (e) => {
              stop(e);
              toggleHidden(courseId);
              setOpen(false);
              triggerRef.current?.focus();
            },
            children: [
              /* @__PURE__ */ jsxDEV(Icon, { name: isHidden ? "eye" : "eye-slash", className: "courseoverview-menu__icon" }, void 0, false, {
                fileName: "public/blocks/myoverview/js/esm/src/components/EllipsisMenu.tsx",
                lineNumber: 102,
                columnNumber: 25
              }, this),
              isHidden ? strings.showcourse : strings.hidecourse
            ]
          },
          void 0,
          true,
          {
            fileName: "public/blocks/myoverview/js/esm/src/components/EllipsisMenu.tsx",
            lineNumber: 91,
            columnNumber: 21
          },
          this
        )
      },
      void 0,
      false,
      {
        fileName: "public/blocks/myoverview/js/esm/src/components/EllipsisMenu.tsx",
        lineNumber: 84,
        columnNumber: 17
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/blocks/myoverview/js/esm/src/components/EllipsisMenu.tsx",
    lineNumber: 64,
    columnNumber: 9
  }, this);
}
__name(EllipsisMenu, "EllipsisMenu");
export {
  EllipsisMenu as default
};
//# sourceMappingURL=EllipsisMenu.dev.js.map
