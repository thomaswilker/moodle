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
 define(['jquery', 'core/url', 'core/templates', 'core/notification', 'core/str', 'core/ajax', 'core/dragdrop-reorder', 'core/tree'],
        function($, url, templates, notification, str, ajax, dragdrop, ariatree) {

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

     var doMove = function() {
         debugger;
     };

     var moveSetTarget = function() {
         debugger;
     };

     var moveHandler = function(e) {
         e.preventDefault();
         var competency = $('[data-region="competencyactions"]').data('competency');

         var requests = ajax.call([
             {
                 methodname: 'tool_learningplan_search_competencies',
                 args: {
                     competencyframeworkid: competency.competencyframeworkid,
                     searchtext: ''
                 }
             },{
                 methodname: 'tool_learningplan_read_competency_framework',
                 args: {
                     id: competency.competencyframeworkid
                 }
             }
         ]);

         $.when.apply(null, requests).done(function(competencies, framework) {

             templates.render('tool_learningplan/competency_summary', competency)
                .done(function(summary) {

                    var strings = str.get_strings([
                        { key: 'move', component: 'tool_learningplan' },
                        { key: 'movecompetency', component: 'tool_learningplan', param: summary },
                        { key: 'move', component: 'tool_learningplan' },
                        { key: 'cancel', component: 'tool_learningplan' }
                    ]).done(function (strings) {

                        var context = {
                            framework: framework,
                            competencies: competencies
                        };

                        templates.render('tool_learningplan/competencies_move_tree', context)
                           .done(function(tree) {
                               notification.confirm(
                                   strings[0], // Move
                                   strings[1] + tree, // Move competency X?
                                   strings[2], // Move.
                                   strings[3], // Cancel.
                                   doMove
                               );

                               var competencytree = new ariatree('[data-enhance=movetree]', moveSetTarget);

                           }).fail(notification.exception);

                    }).fail(notification.exception);
                }).fail(notification.exception);

         }).fail(notification.exception);

     };

     var editHandler = function(e) {
         e.preventDefault();
         var competency = $('[data-region="competencyactions"]').data('competency');

         var params = {
             competencyframeworkid : treeModel.getCompetencyFrameworkId(),
             id : competency.id,
             parentid: competency.parentid
         };

         var queryparams = $.param(params);
         var actionurl = url.relativeUrl('/admin/tool/learningplan/editcompetency.php?' + queryparams);
         window.location = actionurl;
     };

     var reloadPage = function(context) {
        templates.render('tool_learningplan/manage_competencies_page', context)
            .done(function(newhtml, newjs) {
                $('[data-region="managecompetencies"]').replaceWith(newhtml);
                templates.runTemplateJS(newjs);
            })
            .fail(notification.exception);
     };

     var doDelete = function() {
        // We are chaining ajax requests here.
        var competency = $('[data-region="competencyactions"]').data('competency');
        var requests = ajax.call([{
            methodname: 'tool_learningplan_delete_competency',
            args: { id: competency.id }
        }, {
            methodname: 'tool_learningplan_data_for_competencies_manage_page',
            args: { competencyframeworkid: competency.competencyframeworkid }
        }]);
        requests[1].done(reloadPage).fail(notification.exception);
     };

     var deleteHandler = function(e) {
         e.preventDefault();
         var competency = $('[data-region="competencyactions"]').data('competency');

         templates.render('tool_learningplan/competency_summary', competency)
            .done(function(html) {

                var strings = str.get_strings([
                    { key: 'confirm', component: 'tool_learningplan' },
                    { key: 'deletecompetency', component: 'tool_learningplan', param: html },
                    { key: 'delete', component: 'tool_learningplan' },
                    { key: 'cancel', component: 'tool_learningplan' }
                ]).done(function (strings) {
                     notification.confirm(
                        strings[0], // Confirm.
                        strings[1], // Delete competency X?
                        strings[2], // Delete.
                        strings[3], // Cancel.
                        doDelete
                     );
                }).fail(notification.exception);
            }).fail(notification.exception);

     };

     return {
         init: function(model) {
             treeModel = model;
             $('[data-region="competencyactions"] [data-action="add"]').on('click', addHandler);
             $('[data-region="competencyactions"] [data-action="edit"]').on('click', editHandler);
             $('[data-region="competencyactions"] [data-action="delete"]').on('click', deleteHandler);
             $('[data-region="competencyactions"] [data-action="move"]').on('click', moveHandler);
         },
         // Public variables and functions.
         selectionChanged: function(node) {
             var id = $(node).data('id');
             if (typeof id === "undefined") {
                 // Assume this is the root of the tree.
                 // Here we are only getting the text from the top of the tree, to do it we clone the tree,
                 // remove all children and then call text on the result.
                 $('[data-region="competencyinfo"]').html(node.clone().children().remove().end().text());
                 $('[data-region="competencyactions"]').data('competency', null);
                 $('[data-region="competencyactions"] [data-action="add"]').removeAttr("disabled");
                 $('[data-region="competencyactions"] [data-action="edit"]').attr('disabled', 'disabled');
                 $('[data-region="competencyactions"] [data-action="move"]').attr('disabled', 'disabled');
                 $('[data-region="competencyactions"] [data-action="delete"]').attr('disabled', 'disabled');
             } else {
                 var competency = treeModel.getCompetency(id);

                 templates.render('tool_learningplan/competency_summary', competency)
                    .done(function(html) {
                         $('[data-region="competencyinfo"]').html(html);
                    }).fail(notification.exception);

                 $('[data-region="competencyactions"]').data('competency', competency);
                 $('[data-region="competencyactions"] [data-action="add"]').removeAttr("disabled");
                 $('[data-region="competencyactions"] [data-action="edit"]').removeAttr('disabled');
                 $('[data-region="competencyactions"] [data-action="move"]').removeAttr('disabled');
                 $('[data-region="competencyactions"] [data-action="delete"]').removeAttr('disabled');

             }
         }
     };
 });
