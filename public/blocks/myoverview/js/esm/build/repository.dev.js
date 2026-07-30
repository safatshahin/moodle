var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { fetchOne } from "@moodle/lms/core/ajax";
import Fetch from "@moodle/lms/core/fetch";
const CARDLIST_REQUIRED_FIELDS = [
  "id",
  "fullname",
  "shortname",
  "coursecategory",
  "showshortname",
  "visible",
  "enddate"
];
const SUMMARY_REQUIRED_FIELDS = [...CARDLIST_REQUIRED_FIELDS, "summary", "summaryformat"];
const SORT_SQL_MAP = {
  title: "fullname",
  shortname: "shortname",
  lastaccessed: "ul.timeaccess desc",
  startdate: "startdate"
};
const PREF_VIEW = "block_myoverview_user_view_preference";
const PREF_FILTER = "block_myoverview_user_grouping_preference";
const PREF_SORT = "block_myoverview_user_sort_preference";
const PREF_CFVALUE = "block_myoverview_user_grouping_customfieldvalue_preference";
const hiddenPrefName = /* @__PURE__ */ __name((courseId) => `block_myoverview_hidden_course_${courseId}`, "hiddenPrefName");
function decodeEntities(encoded) {
  const textarea = document.createElement("textarea");
  textarea.innerHTML = encoded;
  return textarea.value;
}
__name(decodeEntities, "decodeEntities");
async function getCourses(args) {
  const { sort, view, ...rest } = args;
  const response = await fetchOne({
    methodname: "core_course_get_enrolled_courses_by_timeline_classification",
    args: {
      ...rest,
      sort: SORT_SQL_MAP[sort],
      requiredfields: view === "summary" ? SUMMARY_REQUIRED_FIELDS : CARDLIST_REQUIRED_FIELDS
    }
  });
  return {
    ...response,
    courses: response.courses.map((c) => ({
      ...c,
      fullnamedisplay: decodeEntities(c.fullnamedisplay),
      coursecategory: decodeEntities(c.coursecategory ?? "")
    }))
  };
}
__name(getCourses, "getCourses");
async function setFavourite(courseId, favourite) {
  return fetchOne({
    methodname: "core_course_set_favourite_courses",
    args: { courses: [{ id: courseId, favourite }] }
  });
}
__name(setFavourite, "setFavourite");
async function writePreference(name, value) {
  await Fetch.performPost("core_user", `current/preferences/${name}`, { body: { value } });
}
__name(writePreference, "writePreference");
async function setPreference(name, value) {
  return writePreference(name, value);
}
__name(setPreference, "setPreference");
async function setCourseHidden(courseId, hidden) {
  return writePreference(hiddenPrefName(courseId), hidden ? "1" : null);
}
__name(setCourseHidden, "setCourseHidden");
export {
  PREF_CFVALUE,
  PREF_FILTER,
  PREF_SORT,
  PREF_VIEW,
  getCourses,
  hiddenPrefName,
  setCourseHidden,
  setFavourite,
  setPreference
};
//# sourceMappingURL=repository.dev.js.map
