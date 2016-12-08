/**
 * Handle ajax requests for messages (delete)
 *
 * @module     block_oua_connections/manage_connections
 * @package    block_oua_connections
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'core/ajax', 'core/log', 'core/notification', 'core/templates', 'core/config', 'core/str', 'block_oua_connections/jquery.blockUI'],
    function ($, ajax, log, notification, templates, config, str, blockui) {
        var $blockedelement;
        var $modal;
        var ajax_delete_connection = function ($userid) {
            // Remove request notification?
            var promises = ajax.call([{
                methodname: 'block_oua_connections_delete_connection',
                args: {
                    userid: $userid
                }
            }]);

            $.when.apply($, promises)
                .done(function ($updatereturnobj) {
                    if($updatereturnobj.allmyconnections) {
                        $("#allmyconnections").replaceWith($updatereturnobj.allmyconnections);
                    }

                    if($.isFunction($blockedelement.unblock)) {
                        $blockedelement.unblock();
                    }
                })
                .fail(notification.exception);
        };

        $('#allmyconnections').parent().on('click', ' #allmyconnections button.connection-delete', function () {
            var $deletebtn = $(this);
            // var $msgpanel = $deletebtn.parents('.panel-body');
            $modal = $('#confirm_delete_connection_dialog');
            $modal.modal({backdrop:true});
            $('.confirm-delete',$modal).data('userid', $deletebtn.data('userid'));
        }).on('click', '.confirm-delete', function ($evt) {
            var $userid = $(this).data('userid');
            $blockedelement = $('#confirm_delete_connection_dialog').parent().parent();
            $blockedelement.block({message: "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>"});
            $modal.modal('hide');
            ajax_delete_connection($userid);
        });

        // This module does not expose anything.
        return {};
    });
