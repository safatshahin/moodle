import{fetchOne as t}from"@moodle/lms/core/ajax";/**
 * Dashboard web-service repository.
 *
 * @module     core_my/repository
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const n=e=>t({methodname:"core_my_get_dashboard",args:{sitedefault:e}}),l=e=>t({methodname:"core_block_fetch_addable_blocks",args:{pagecontextid:0,pagetype:"",pagelayout:"",subpage:"",pagehash:e}}),d=(e,a,o=[],r="",s=0)=>t({methodname:"core_my_update_dashboard",args:{action:e,sitedefault:a,layout:o,blockname:r,blockid:s}});export{l as fetchAddableBlocks,n as getDashboard,d as updateDashboard};
