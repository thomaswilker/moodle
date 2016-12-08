/**
 * Handle ajax requests for notifications (delete)
 *
 * @module     local_conversations/manage_notifications
 * @package    local_conversations
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'core/ajax', 'core/log', 'core/notification', 'core/templates', 'core/config', 'core/str', 'local_conversations/jquery.blockUI',
        'block_oua_connections/request_connect'],
    function ($, ajax, log, notification, templates, config, str, blockui, oua_connections) {
        var $blockedelement;
        var $loadingimg = "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>";

        /*
         On ajax failure display a log back in message, or other error.
         */
        var ajax_fail = function ($ex) {
            str.get_strings([
                {key: 'error'},
                {key: 'loggedout', component: 'local_conversations'},
                {key: 'reload'},
                {key: 'cancel'},
            ]).done(function (s) {
                if (typeof $ex.errorcode !== 'undefined') {
                    switch ($ex.errorcode) {
                        case 'servicenotavailable':
                            notification.confirm(s[0], s[1], s[2], s[3], function () {
                                window.location.reload(true);
                            });
                            break;
                        default:
                            notification.exception($ex);
                            break;
                    }
                }
            }).fail(function ($newex) {
                // We have really really failed.
                notification.exception($ex + ' ' + $newex);
            });
        };

        var get_all_notifications_ajax = {
            methodname: 'local_conversations_get_all_notifications',
            args: {},
            done: function ($return) {
                templates.render('local_conversations/notification_list', $return.notifications).done(function (notification_list) {
                    unblockBlockedElement();
                    templates.replaceNode('#notifications', notification_list, '');
                });
            },
            fail: ajax_fail
        };
        var refresh_header_ajax = {
            methodname: 'local_conversations_get_cached_header_previews',
            args: {},
            done: function ($return) {
                templates.render('local_conversations/partials_notification_alert', $return.notification_preview_cache)
                    .done(function (notification_preview) {
                        unblockBlockedElement();
                        var $newnotificationpreview = $(notification_preview);
                        $('header .notification-count .dropdown-menu > ul').replaceWith($newnotificationpreview.find('.dropdown-menu > ul'));
                        $('header .notification-count .total-count.badge').replaceWith($newnotificationpreview.find('.total-count.badge'));
                    });
                templates.render('local_conversations/partials_message_alert', $return.conversation_preview_cache)
                    .done(function (message_preview) {
                        unblockBlockedElement();
                        var $newmessagepreview = $(message_preview);
                        $('header .message-count .dropdown-menu > ul').replaceWith($newmessagepreview.find('.dropdown-menu > ul'));
                        $('header .message-count .total-count.badge').replaceWith($newmessagepreview.find('.total-count.badge'));
                    });
            },
            fail: ajax_fail
        };

        var ajax_delete_notifications = function ($notificationids) {
            blockElement($('#allnotifications .notifications-list'));
            // Remove request notification?
            ajax.call([
                {
                    methodname: 'local_conversations_delete_messages_by_id',
                    args: {
                        messageids: $notificationids
                    },
                    // done: local_reload_message_alerts, // load after finish, in 2nd request
                    fail: ajax_fail
                }, get_all_notifications_ajax, refresh_header_ajax // Return in same request.
            ]);
        };

        var ajax_mark_notifications_read = function ($notificationids) {
            ajax.call([{
                methodname: 'local_conversations_mark_messages_read_by_id',
                args: {
                    messageids: $notificationids
                },
                done: function () {
                    $("#notifications .panel.unread").filter(function(){
                        return ($notificationids.indexOf($(this).data('messageid')) != -1);
                    }).removeClass('unread');
                    unblockBlockedElement();
                },
                fail: ajax_fail
            }, refresh_header_ajax]);
        };

        $('#allnotifications').on('click', '.notification-delete', function () {
            var $messageid = $(this).data('messageid');
            var $modal = $('#confirm_delete_notification_dialog');
            $modal.modal({backdrop: true});
            $('.confirm-delete-one', $modal).data('messageid', [$messageid]);

        }).on('click', '.confirm-delete-one', function ($evt) {
            var $notificationid = $(this).data('messageid');
            ajax_delete_notifications($notificationid);
        }).on('click', 'a.delete-all-notification', function ($evt) {
            $evt.preventDefault();
            var $modal = $('#confirm_delete_all_notification_dialog');
            var $allnotificationids = $("#notifications .panel").not('[data-isconnectionrequest=1]').map(function () {
                return $(this).data('messageid');
            }).toArray();
            if ($allnotificationids.length === 0) {
                return;
            }
            $('.confirm-delete-all', $modal).data('messageids', $allnotificationids);
            $modal.modal({backdrop: true});
        }).on('click', '.confirm-delete-all', function ($evt) {
            var $notificationids = $(this).data('messageids');
            ajax_delete_notifications($notificationids);
        }).on('click', 'a.mark-all-as-read', function ($evt) {
            $evt.preventDefault();
            var $modal = $('#confirm_markallnotificationsasread_dialog');
            var $allnotificationids = $("#notifications .panel.unread").map(function () {
                return $(this).data('messageid');
            }).toArray();
            if ($allnotificationids.length === 0) {
                return;
            }
            $('.confirm-markallnotificationsasread', $modal).data('messageids', $allnotificationids);
            $modal.modal({backdrop: true});
        }).on('click', '.confirm-markallnotificationsasread', function ($evt) {
            blockElement($('#allnotifications .notifications-list'));
            var $notificationids = $(this).data('messageids');
            ajax_mark_notifications_read($notificationids);
        });


        $('#allnotifications, header .notification-counter, #profile-block').on('click', '.connectaccept, .connectignore', function ($evt) {
            $evt.preventDefault();
            $evt.stopPropagation();
            var $userid = $(this).data('userid');
            var $messageid = $(this).data('messageid');
            var $thiselem = $(this);
            if ($(this).parents(".notificationpopup").length !== 0) {
                blockElement($(this).closest("li"));
            } else {
                blockElement($(this).closest(".panel"));
            }
            if ($thiselem.is('.connectignore')) {
                oua_connections.ajax_ignore_request_connection($messageid, $userid, local_reload_message_alerts);
            } else if ($thiselem.is('.connectaccept')) {
                oua_connections.ajax_accept_request_connection($messageid, $userid, local_reload_message_alerts);
            }
        }).on('click', '.marknotificationread', function ($evt) {
            $evt.preventDefault();
            $evt.stopPropagation();
            var $messageid = $(this).data('messageid');
            if ($(this).parents(".dropdown-menu").length !== 0) {
                blockElement($(this).closest("li"));
            }
            ajax_mark_notifications_read([$messageid]);
        });

        var blockElement = function ($element) {
            $blockedelement = $element;
            $blockedelement.block({message: $loadingimg});
        };

        var unblockBlockedElement = function () {
            if ($blockedelement && $.isFunction($blockedelement.unblock)) {
                $blockedelement.unblock();
            }
        };
        var $refresh_header_poll;
        var $header_alert_refresh_time = 120000; // 2 minutes
        var local_reload_message_alerts = function () {
            clearTimeout($refresh_header_poll);
            var $refreshajax = [refresh_header_ajax];
            if ($("#allnotifications").length !== 0) {
                $refreshajax.push(get_all_notifications_ajax);
            }
            ajax.call($refreshajax);
            $refresh_header_poll = setTimeout(function () {
                local_reload_message_alerts();
            }, $header_alert_refresh_time);
        };

        return {
            initialise: function ($header_refresh_setting) {
                if (parseInt($header_refresh_setting) >= 1000) { // Minimum 1000 or we will flood ajax requests.
                    $header_alert_refresh_time = $header_refresh_setting;
                }
                $refresh_header_poll = setTimeout(function () {
                    local_reload_message_alerts();
                }, $header_alert_refresh_time);
            },
            reload_message_alerts: local_reload_message_alerts,
            ajax_mark_notification_read: ajax_mark_notifications_read
        };
    });
