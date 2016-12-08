/**
 * Handle ajax connection requests, accepts and ignore
 *
 * @module     block_oua_connections/request_connect
 * @package    block_oua_connections
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'core/ajax', 'core/log', 'core/notification', 'core/templates', 'core/config', 'core/str', 'block_oua_connections/jquery.blockUI'],
    function ($, ajax, log, notification, templates, config, str, blockui) {
        var $blockedelement;
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
        var update_to_requested = function ($userid, $success) {
            str.get_string('requested', 'block_oua_connections').done(function (s) {
                $('button.btn-connect[data-userid="' + $userid + '"]').text(s).addClass('requested').prop('disabled', true);
            }).fail(notification.exception);
            $("#notification_count").change(); // Trigger change to update notification count.
        };


        var ajax_send_request_connection = function ($userid) {
            var promises = ajax.call([{
                methodname: 'block_oua_connections_request_connection',
                args: {
                    userid: $userid
                }
            }]);

            // When returns a new promise that is resolved when all the passed in promises are resolved.
            // The arguments to the done become the values of each resolved promise.
            $.when.apply($, promises)
                .done(function ($success) {
                    update_to_requested($userid, $success);
                    if($.isFunction($blockedelement.unblock)) {
                        $blockedelement.unblock();
                    }
                })
                .fail(notification.exception);
        };

        // Add the event listeners.
        $('#suggestedconnections').on('click', 'div.user-info button.btn-connect', function () {
            var $userid = $(this).data('userid');
            $blockedelement = $(this).parents('.user-info');
            $blockedelement.block({message: "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>"});

           ajax_send_request_connection($userid);
        });
        $('#myconnections div.nomyconnections').on('click', 'a[href="#suggestedconnections"]', function ($evt) {
            $evt.preventDefault();
            $('a[href="' + $(this).attr('href') + '"]').tab('show');
        });

        // This module does not expose anything.
        return {
            ajax_accept_request_connection: function ($messageid, $userid, cb) {
                // Remove request notification?
                ajax.call([{
                    methodname: 'block_oua_connections_accept_request_connection',
                    args: {
                        messageid: $messageid,
                        userid: $userid
                    },
                    done: function ($updatereturnobj) {
                        cb();
                    },
                    fail: ajax_fail
                }]);
            },
            ajax_ignore_request_connection: function ($messageid, $userid, cb) {
                // Remove request notification?
                 ajax.call([{
                    methodname: 'block_oua_connections_ignore_request_connection',
                    args: {
                        messageid: $messageid,
                        userid: $userid
                    },
                    done: function ($updatereturnobj) {
                        cb();
                    },
                     fail: ajax_fail
                }]);
            }

        };
    });
