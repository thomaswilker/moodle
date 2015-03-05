// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handle selection changes and actions on the competency tree.
 *
 * @module     tool_learningplan/competencyselect
 * @package    tool_learningplan
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 define(['jquery', 'core/url'], function($, url) {
     // Private variables and functions.
     var treeModel = null;

     var addHandler = function(e) {
         e.preventDefault();
         var parent = $('[data-region="competencyactions"]').data('competency');

         var params = {
             competencyframeworkid : treeModel.getCompetencyFrameworkId()
         };

         if (parent == null) {
             // We are adding at the root node.
         } else {
             // We are adding at a sub node.
             params['parentid'] = parent.id;
         }
         var queryparams = $.param(params);
         var actionurl = url.relativeUrl('/admin/tool/learningplan/editcompetency.php?' + queryparams);
         window.location = actionurl;
     };

     return {
         init: function(model) {
             treeModel = model;
             $('[data-region="competencyactions"] [data-action="add"]').on('click', addHandler);

         },
         // Public variables and functions.
         selectionChanged: function(node) {
             var id = $(node).data('id');
             if (typeof id === "undefined") {
                 // Assume this is the root of the tree.
                 $('[data-region="competencyinfo"]').html(node.text());
                 $('[data-region="competencyactions"]').data('competency', null);
                 $('[data-region="competencyactions"] [data-action="add"]').removeAttr("disabled");
                 $('[data-region="competencyactions"] [data-action="edit"]').attr('disabled', 'disabled');
                 $('[data-region="competencyactions"] [data-action="move"]').attr('disabled', 'disabled');
                 $('[data-region="competencyactions"] [data-action="delete"]').attr('disabled', 'disabled');
             } else {
                 var competency = treeModel.getCompetency(id);
                 $('[data-region="competencyactions"]').data('competency', competency);
                 $('[data-region="competencyactions"] [data-action="add"]').removeAttr("disabled");
                 $('[data-region="competencyactions"] [data-action="edit"]').removeAttr('disabled');
                 $('[data-region="competencyactions"] [data-action="move"]').removeAttr('disabled');
                 $('[data-region="competencyactions"] [data-action="delete"]').removeAttr('disabled');

             }
         }
     };
 });
