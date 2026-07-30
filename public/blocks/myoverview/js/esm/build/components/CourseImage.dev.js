var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import { useEffect, useState } from "react";
function CourseImage({ src, className = "" }) {
  const [errored, setErrored] = useState(false);
  useEffect(() => setErrored(false), [src]);
  const showImage = src !== null && !errored;
  return /* @__PURE__ */ jsxDEV("div", { className: `courseoverview-image ${showImage ? "" : "courseoverview-image--empty"} ${className}`.trim(), children: showImage && /* @__PURE__ */ jsxDEV(
    "img",
    {
      src,
      alt: "",
      className: "courseoverview-image__img",
      loading: "lazy",
      onError: () => setErrored(true)
    },
    void 0,
    false,
    {
      fileName: "public/blocks/myoverview/js/esm/src/components/CourseImage.tsx",
      lineNumber: 48,
      columnNumber: 17
    },
    this
  ) }, void 0, false, {
    fileName: "public/blocks/myoverview/js/esm/src/components/CourseImage.tsx",
    lineNumber: 46,
    columnNumber: 9
  }, this);
}
__name(CourseImage, "CourseImage");
export {
  CourseImage as default
};
//# sourceMappingURL=CourseImage.dev.js.map
