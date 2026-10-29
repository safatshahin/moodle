import{jsx as r}from"react/jsx-runtime";/**
 * A control icon rendered by the server through the theme's icon system.
 *
 * The markup comes from $OUTPUT->pix_icon() via the get_dashboard payload (see DashboardIcons in
 * repository.ts), so the theme's icon map decides which glyph is drawn; nothing here names a
 * FontAwesome class. The icon is always decorative: every control it appears in carries its own
 * accessible name.
 *
 * The element is always an <i>, because that is what the design system Button accepts as a
 * startIcon: it checks the React element's own type (an <i> or <svg>; anything else, including a
 * component that would render one, is dropped). That is why renderIcon() returns the element
 * itself rather than a component. The usual FontAwesome markup is a single <i> carrying the
 * glyph's classes, which becomes that element directly; any other icon system's markup (an
 * <img>, say) is rendered inside the <i>.
 *
 * @module     core_my/components/Icon
 * @copyright  2026 A K M Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const o=/^\s*<i\b(?<attributes>[^>]*)>\s*<\/i>\s*$/iu,c=/\bclass\s*=\s*"(?<classes>[^"]*)"/iu,a=(s,e="")=>{const t=o.exec(s);if(t){const n=c.exec(t.groups?.attributes??"")?.groups?.classes??"";return r("i",{className:`core-my-dashboard-icon ${n} ${e}`.trim(),"aria-hidden":"true"})}return r("i",{className:`core-my-dashboard-icon ${e}`.trim(),"aria-hidden":"true",dangerouslySetInnerHTML:{__html:s}})},i=({markup:s,className:e=""})=>a(s,e);var l=i;export{l as default,a as renderIcon};
