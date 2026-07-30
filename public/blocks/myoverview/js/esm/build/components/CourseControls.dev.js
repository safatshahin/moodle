var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import { useCourseMemberships } from "../state";
import StarButton from "@moodle/lms/block_myoverview/components/StarButton";
import EllipsisMenu from "@moodle/lms/block_myoverview/components/EllipsisMenu";
function CourseControls({ course }) {
  const { favourites, hidden } = useCourseMemberships();
  return /* @__PURE__ */ jsxDEV("div", { className: "courseoverview-controls", children: [
    /* @__PURE__ */ jsxDEV(
      StarButton,
      {
        courseId: course.id,
        courseName: course.fullnamedisplay,
        isFavourite: favourites.has(course.id)
      },
      void 0,
      false,
      {
        fileName: "public/blocks/myoverview/js/esm/src/components/CourseControls.tsx",
        lineNumber: 49,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(
      EllipsisMenu,
      {
        courseId: course.id,
        courseName: course.fullnamedisplay,
        isHidden: hidden.has(course.id)
      },
      void 0,
      false,
      {
        fileName: "public/blocks/myoverview/js/esm/src/components/CourseControls.tsx",
        lineNumber: 54,
        columnNumber: 13
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/blocks/myoverview/js/esm/src/components/CourseControls.tsx",
    lineNumber: 48,
    columnNumber: 9
  }, this);
}
__name(CourseControls, "CourseControls");
export {
  CourseControls as default
};
//# sourceMappingURL=CourseControls.dev.js.map
