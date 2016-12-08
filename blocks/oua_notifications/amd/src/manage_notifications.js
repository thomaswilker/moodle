/**
 * Handle ajax requests for notifications (delete)
 *
 * @module     block_oua_notifications/manage_notifications
 * @package    block_oua_notifications
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'core/ajax', 'core/log', 'core/notification', 'core/templates', 'core/config', 'core/str', 'block_oua_notifications/jquery.blockUI'],
    function ($, ajax, log, notification, templates, config, str, blockui) {
        var $blockedelement;
        var ajax_delete_notifications = function ($notificationids) {

            // Remove request notification?
            var promises = ajax.call([{
                methodname: 'block_oua_notifications_delete_notifications',
                args: {
                    notificationids: $notificationids
                }
            }]);

            $.when.apply($, promises)
                .done(function ($updatereturnobj) {
                    if($updatereturnobj.notification_list) {
                        $("#notification_accordion").replaceWith($updatereturnobj.notification_list);
                        $("#notification_count").change(); // Trigger change to update notification count.
                    }

                    if($blockedelement && $.isFunction($blockedelement.unblock)) {
                        $blockedelement.unblock();
                    }
                })
                .fail(notification.exception);
        };
        var populate_notification_count = function () {
            var alertCount = $('.block_oua_notifications .accordion').attr('data-count'); /* retrieve count value */
            if ( alertCount === '0' ) {
                alertCount = ''; /* do not show as 0 */
            }
            $('.user-navigation .block_oua_notifications .badge').text( alertCount ); /* show count next to tab */
        };

        $('#notification_accordion').parent().on('click', '.notification-delete', function () {
            var $deletebtn = $(this);
            var $msgpanel = $deletebtn.parents('.panel-body');
            var $modal = $('#confirm_delete_notification_dialog');
            $modal.appendTo($msgpanel);

            $msgpanel.css('position', 'relative');
            $modal.modal({backdrop:true});
            $('.confirm-delete', $modal).data('notificationid', [$deletebtn.data('notificationid')]);
            $('.modal-backdrop.in').appendTo($msgpanel).css('bottom',0);// Override bootstrap backdrop style.
            $('body').removeClass('modal-open').css('padding-right',0); // Override bootstrap extra padding-right on body.

         }).on('click', '.confirm-delete', function ($evt) {
            var $notificationid = $(this).data('notificationid');
            $blockedelement = $('#notification_accordion').parent();
            $blockedelement.block({message: "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>"});
            ajax_delete_notifications($notificationid);
        }).on('click', 'a.dismissall', function ($evt) {
            $evt.preventDefault();
            var $deletebtn = $(this);
            var $msgpanel = $deletebtn.parents('.panel-group');
            var $modal = $('#confirm_delete_all_notification_dialog');
            $msgpanel.css('position', 'relative');
            $modal.appendTo($msgpanel);

            $modal.modal({backdrop:true});
            $('.confirm-delete-all', $modal).data('notificationids', $(this).parents("#dismiss-all").data("dismissids"));
            $('.modal-backdrop.in').appendTo($msgpanel).css('bottom',0); // Override bootstrap backdrop style.
            $('body').removeClass('modal-open').css('padding-right',0); // Override bootstrap extra padding-right on body.
        }).on('click', '.confirm-delete-all', function ($evt) {
            var $notificationids = $(this).data('notificationids');
            $blockedelement = $('#notification_accordion').parent();
            $blockedelement.block({message: "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>"});
            ajax_delete_notifications($notificationids);
        }).on('change', '#notification_count', function(){
            populate_notification_count();
        });

        var ajax_mark_notification_read = function ($notificationid) {
            ajax.call([{
                methodname: 'block_oua_notifications_mark_notification_read',
                args: {
                    notificationid: $notificationid
                },
                fail: notification.exception
            }]);
        };

        $('#notification_accordion').parent().on('click', '.panel-heading.new-message', function () {
            var $countnewnotification = $('#notification_accordion').attr('data-count');
            if ($countnewnotification > 0) {
                $('#notification_accordion').attr('data-count', $countnewnotification - 1);
                populate_notification_count();
            }
            // Message is now read.
            $(this).removeClass('new-message');
            var $notificationid = $(this).attr('data-notificationid');
            ajax_mark_notification_read($notificationid);
        });

        $( document ).ready( function() {
            populate_notification_count();
        });

        // This module does not expose anything.
        return {};
    });
