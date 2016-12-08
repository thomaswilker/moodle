/**
 * Handle ajax requests for messages (delete)
 *
 * @module     block_oua_messages/manage_messages
 * @package    block_oua_messages
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'core/ajax', 'core/log', 'core/notification', 'core/templates', 'core/config', 'core/str', 'block_oua_messages/jquery.blockUI'],
    function ($, ajax, log, notification, templates, config, str, blockui) {
        var $blockedelement;
        var ajax_delete_message = function ($messageid) {

            // Remove request notification?
            var promises = ajax.call([{
                methodname: 'block_oua_messages_delete_message',
                args: {
                    messageid: $messageid
                }
            }]);

            $.when.apply($, promises)
                .done(function ($updatereturnobj) {
                    if($updatereturnobj.message_list) {
                        $("#message_accordion").replaceWith($updatereturnobj.message_list);
                        $("#message_count").change(); // Trigger change to update message count.
                    }

                    if($.isFunction($blockedelement.unblock)) {
                        $blockedelement.unblock();
                    }
                })
                .fail(notification.exception);
        };
        var populate_message_count = function () {
            var alertCount = $('.block_oua_messages .accordion').attr('data-count'); /* retrieve count value */
            if ( alertCount === '0' ) {
                alertCount = ''; /* do not show as 0 */
            }
            $('.user-navigation .block_oua_messages .badge').text( alertCount ); /* show count next to tab */
        };

        $('#message_accordion').parent().on('click', '.message-delete', function () {
            var $deletebtn = $(this);
            var $msgpanel = $deletebtn.parents('.panel-body');
            var $modal = $('#confirm_delete_dialog');
            $modal.appendTo($msgpanel);

            $msgpanel.css('position', 'relative');
            $modal.modal({backdrop:true});
            $('.confirm-delete',$modal).data('messageid', $deletebtn.data('messageid'));
            $('.modal-backdrop.in').appendTo($msgpanel).css('bottom',0);// Override bootstrap backdrop style.
            $('body').removeClass('modal-open').css('padding-right',0); // Override bootstrap extra padding-right on body.

         }).on('click', '.confirm-delete', function ($evt) {
            var $messageid = $(this).data('messageid');
            $blockedelement = $('#notification_accordion').parent();
            $blockedelement.block({message: "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>"});
            ajax_delete_message($messageid);
        }).on('change', '#message_count', function(){
            populate_message_count();
        });

        var ajax_mark_message_read = function ($messageid) {
            ajax.call([{
                methodname: 'block_oua_messages_mark_message_read',
                args: {
                    messageid: $messageid
                },
                fail: notification.exception
            }]);
        };

        $('#message_accordion').parent().on('click', '.panel-heading.new-message', function () {
            var $this = $(this);
            var $countnewmessage = $('#message_accordion').attr('data-count');
            if ($countnewmessage > 0) {
                $('#message_accordion').attr('data-count', $countnewmessage - 1);
                populate_message_count();
            }
            // Message is now read.
            $this.removeClass('new-message');
            var $messageid = $this.attr('data-messageid');
            ajax_mark_message_read($messageid);
        });

        $( document ).ready( function() {
            populate_message_count();
        });

        // This module does not expose anything.
        return {};
    });
