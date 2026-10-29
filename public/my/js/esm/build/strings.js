import{useEffect as l,useState as d}from"react";import g from"@moodle/lms/core/pending";import{getString as y,getStrings as b}from"@moodle/lms/core/stringUtils";/**
 * Language strings for the dashboard application.
 *
 * Strings are fetched through core/str (the stringUtils ESM module), as the React guidelines ask,
 * rather than shipped through the get_dashboard web service payload: the fixed set the application
 * needs is fetched once in a single batched request by useDashboardLabels(), and the per-block
 * strings (which take the block's title as a placeholder) by useString(), which leaves the
 * placeholder substitution to Moodle's own string formatting instead of reimplementing it here.
 *
 * @module     core_my/strings
 * @copyright  2026 A K M Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const i={addblock:{key:"addblock",component:"core"},addblockhere:{key:"addblockhere",component:"my"},blocks:{key:"blocks_main",component:"core"},cancel:{key:"cancel",component:"core"},confirm:{key:"confirm",component:"core"},confirmreset:{key:"confirmresetdashboard",component:"my"},down:{key:"down",component:"core"},left:{key:"dashboardleft",component:"my"},loading:{key:"loading",component:"core"},movecontrols:{key:"dashboardmovecontrols",component:"my"},moveinstructions:{key:"dashboardmovehandleinstructions",component:"my"},reset:{key:"resetpage",component:"my"},resetheading:{key:"resetdashboardheading",component:"my"},resize:{key:"resizeblock",component:"my"},resizecontrols:{key:"dashboardresizecontrols",component:"my"},resizeinstructions:{key:"dashboardresizehandleinstructions",component:"my"},right:{key:"dashboardright",component:"my"},up:{key:"up",component:"core"}},u=()=>{const[n,r]=d(null);return l(()=>{let o=!1;const t=Object.keys(i),a=new g("core_my/dashboard:strings");return b(t.map(e=>i[e])).then(e=>{o||r(Object.fromEntries(t.map((s,c)=>[s,e[c]])))}).catch(()=>{}).finally(()=>{a.resolve()}),()=>{o=!0}},[]),n},f=(n,r,o)=>{const[t,a]=d(""),e=JSON.stringify(o??null);return l(()=>{let s=!1;const c=JSON.parse(e)??void 0;return y(n,r,c).then(m=>{s||a(m)}).catch(()=>{}),()=>{s=!0}},[n,r,e]),t};export{u as useDashboardLabels,f as useString};
