var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { getStrings } from "@moodle/lms/core/stringUtils";
const COMPONENT = "block_myoverview";
const STRING_MAP = {
  actionsfor: { key: "aria:courseactionsfor", component: COMPONENT },
  changelayout: { key: "aria:displaydropdown", component: COMPONENT },
  clearsearch: { key: "clear" },
  courseactions: { key: "aria:courseactions", component: COMPONENT },
  courseoverview: { key: "pluginname", component: COMPONENT },
  courseprogress: { key: "courseprogress", component: COMPONENT },
  createcourse: { key: "createcourse", component: COMPONENT },
  emptyallhiddenintro: { key: "allhidden_intro", component: COMPONENT },
  emptyallhiddentitle: { key: "allhidden_title", component: COMPONENT },
  emptyeducator: { key: "zero_default_intro", component: COMPONENT },
  emptynoresults: { key: "noresults_intro", component: COMPONENT },
  emptynoresultstitle: { key: "noresults_title", component: COMPONENT },
  emptystudent: { key: "zero_default_intro", component: COMPONENT },
  errorloadingcourses: { key: "errorloadingcourses", component: COMPONENT },
  filterall: { key: "allcourses", component: COMPONENT },
  filterallincludinghidden: { key: "allincludinghidden", component: COMPONENT },
  filtercustomfield: { key: "customfield", component: COMPONENT },
  filterfavourites: { key: "favourites", component: COMPONENT },
  filterfuture: { key: "future", component: COMPONENT },
  filterhidden: { key: "hiddencourses", component: COMPONENT },
  filterinprogress: { key: "inprogress", component: COMPONENT },
  filterpast: { key: "past", component: COMPONENT },
  filterresults: { key: "aria:groupingdropdown", component: COMPONENT },
  filters: { key: "filters" },
  hidecourse: { key: "hidecourse", component: COMPONENT },
  managecategories: { key: "managecategories" },
  managecourses: { key: "managecourses" },
  nextpage: { key: "nextpage" },
  percentcomplete: { key: "completepercent", component: COMPONENT },
  previouspage: { key: "previouspage" },
  removefromstarred: { key: "aria:removefromfavouritesfor", component: COMPONENT },
  requestcoursebutton: { key: "requestcoursebutton", component: COMPONENT },
  search: { key: "search" },
  searchcourses: { key: "searchcourses", component: COMPONENT },
  showcourse: { key: "show", component: COMPONENT },
  sortby: { key: "sortby" },
  sortcoursename: { key: "title", component: COMPONENT },
  sortcourses: { key: "aria:sortingdropdown", component: COMPONENT },
  sortlastaccessed: { key: "lastaccessed", component: COMPONENT },
  sortshortname: { key: "shortname", component: COMPONENT },
  sortstartdate: { key: "startdate" },
  starcourse: { key: "aria:addtofavouritesfor", component: COMPONENT },
  tooltipfilter: { key: "filter" },
  tooltipsort: { key: "sort" },
  tooltipview: { key: "view" },
  viewcard: { key: "card", component: COMPONENT },
  viewlabel: { key: "view" },
  viewlist: { key: "list", component: COMPONENT },
  viewsummary: { key: "summary", component: COMPONENT }
};
async function loadStrings() {
  const keys = Object.keys(STRING_MAP);
  const values = await getStrings(keys.map((k) => STRING_MAP[k]));
  const strings = {};
  keys.forEach((k, i) => {
    strings[k] = values[i];
  });
  return strings;
}
__name(loadStrings, "loadStrings");
export {
  loadStrings
};
//# sourceMappingURL=strings.dev.js.map
