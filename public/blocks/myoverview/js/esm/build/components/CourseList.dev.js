var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import CourseItem from "@moodle/lms/block_myoverview/components/CourseItem";
function CourseList({ courses, view, displaycategories }) {
  return /* @__PURE__ */ jsxDEV("div", { className: `courseoverview-list courseoverview-list--${view}`, children: courses.map((course) => /* @__PURE__ */ jsxDEV(
    CourseItem,
    {
      course,
      view,
      displaycategories
    },
    course.id,
    false,
    {
      fileName: "public/blocks/myoverview/js/esm/src/components/CourseList.tsx",
      lineNumber: 44,
      columnNumber: 17
    },
    this
  )) }, void 0, false, {
    fileName: "public/blocks/myoverview/js/esm/src/components/CourseList.tsx",
    lineNumber: 42,
    columnNumber: 9
  }, this);
}
__name(CourseList, "CourseList");
export {
  CourseList as default
};
//# sourceMappingURL=CourseList.dev.js.map
