var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
import { FavouriteButton } from "@moodlehq/design-system";
import { useCourseCallbacks, useStrings } from "../state";
function StarButton({ courseId, courseName, isFavourite }) {
  const { toggleFavourite } = useCourseCallbacks();
  const strings = useStrings();
  const label = isFavourite ? strings.removefromstarred.replace("{$a}", courseName) : strings.starcourse.replace("{$a}", courseName);
  return /* @__PURE__ */ jsxDEV(
    FavouriteButton,
    {
      selected: isFavourite,
      "aria-label": label,
      onClick: (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleFavourite(courseId);
      }
    },
    void 0,
    false,
    {
      fileName: "public/blocks/myoverview/js/esm/src/components/StarButton.tsx",
      lineNumber: 46,
      columnNumber: 9
    },
    this
  );
}
__name(StarButton, "StarButton");
export {
  StarButton as default
};
//# sourceMappingURL=StarButton.dev.js.map
