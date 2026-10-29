import{fetchOne as e}from"@moodle/lms/core/ajax";/**
 * Dashboard web-service repository.
 *
 * @module     core_my/repository
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const i=async t=>e({methodname:"core_my_get_dashboard",args:{sitedefault:t}}),n=async t=>e({methodname:"core_block_fetch_addable_blocks",args:{pagecontextid:0,pagetype:"",pagelayout:"",subpage:"",pagehash:t}}),l=async(t,a,o=[],s="")=>e({methodname:"core_my_update_dashboard",args:{action:t,sitedefault:a,layout:o,blockname:s}});export{n as fetchAddableBlocks,i as getDashboard,l as updateDashboard};
