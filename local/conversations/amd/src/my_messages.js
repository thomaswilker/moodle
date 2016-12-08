/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'theme_bootstrap/bootstrap', 'core/ajax', 'core/log',
        'core/notification', 'core/templates', 'core/config', 'core/str',
        'local_conversations/jquery.autogrow', 'local_conversations/jquery.blockUI'],
    function ($, bs, $ajax, log, notification, template, cfg, str, grow) {
        var $conversation_refresh_time = 60000;
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
        var send_message = function ($useridto, $message) {
            var $args = {
                useridto: $useridto,
                message: $message
            };
            $ajax.call([{
                methodname: 'local_conversations_send_message',
                args: $args,
                done: function ($newmessagereturn) {
                    $(document).trigger('local_conversations_message_sent', {useridto: $useridto});
                },
                fail: ajax_fail
            }]);
        };
        $(document).on('local_conversations_message_sent', function ($evt, $data) {
            clear_message_box();
            reset_contact_list($data.useridto);
            $("#new-message-search input").val('');
        });
        var clear_message_box = function () {
            $("#new-message-edit").val('').trigger('change');
        };
        var $refresh_conversation_poll;
        var refresh_conversation = function ($useridfrom) {
            var $args = {
                useridfrom: $useridfrom
            };
            /* poll conversation every $conversation_refresh_time */
            clearTimeout($refresh_conversation_poll);
            $ajax.call([{
                methodname: 'local_conversations_get_conversation',
                args: $args,
                done: function ($return) {
                    update_unread_badge($return.conversation_preview_cache);
                    var $conversations = $return.messages;
                    if ($useridfrom in $conversations) {
                        var templatePromise = template.render('local_conversations/conversation', $conversations[$useridfrom]);
                        templatePromise.done(function (source, javascript) {
                            var $conversation = $("#conversation-" + $useridfrom);
                            var $oldScrollHeight = $conversation.prop("scrollHeight");
                            var $oldScrollTop = $conversation.prop("scrollTop");
                            var $newconversation = $(source);
                            $conversation.after($newconversation);
                            var $newScrollHeight = $newconversation.prop("scrollHeight");

                            if ($oldScrollHeight != $newScrollHeight) {
                                // Messages changed (new or sent) smooth scroll into view.
                                $newconversation.scrollTop($oldScrollTop);
                                $newconversation.css('visibility', 'visible');
                                $conversation.remove();
                                $newconversation.animate({scrollTop: $newScrollHeight}, 550);
                            } else {
                                // Height hasnt changed, means message no new messages but unread could have changed.
                                $newconversation.remove(); // Avoid scrolling if no change.
                                if ($(".unread", $newconversation).length === 0) {
                                    // If messages were marked as read, remove unread classes.
                                    $(".unread", $conversation).removeClass('unread');
                                    remove_unread_class($useridfrom);
                                }
                            }

                        });
                    }
                    /* poll conversation every $conversation_refresh_time */
                    $refresh_conversation_poll = setTimeout(function () {
                        refresh_conversation($useridfrom);
                    }, $conversation_refresh_time);

                    $(document).trigger('local_conversations_conversation_refreshed', {useridfrom: $useridfrom});
                },
                fail: ajax_fail
            }]);
        };

        var mark_messages_read_by_id = function ($messageids, callback) {
            $ajax.call([{
                methodname: 'local_conversations_mark_messages_read_by_id',
                args: {
                    messageids: $messageids
                },
                done: function () {
                    if(callback && typeof callback == "function") {
                        callback();
                    }
                },
                fail: ajax_fail
            }]);
        };
        var update_unread_badge = function ($unreadcountcache) {
            if (!$unreadcountcache.unread_conversation_count) {
                $unreadcountcache.unread_conversation_count = '';
                /* do not show as 0 */
            }
            // There are multiple message count badges (mobile/desktop).
            $('header nav .message-count .total-count.badge').text($unreadcountcache.unread_conversation_count);
            if ($unreadcountcache.unread_conversation_preview !== undefined) {
                template.render('local_conversations/partials_message_alert_preview', {unread_conversation_preview: $unreadcountcache.unread_conversation_preview}).done(function (html, js) {
                    $('header nav .message-count .dropdown-menu').replaceWith(html);
                }).fail(notification.exception);
            }
            if ($unreadcountcache.users_with_unread !== undefined) {
                $unreadcountcache.users_with_unread.forEach(function (item, idx) {
                    var $userli = $("li[data-userid='" + item + "']");
                    if (!$userli.hasClass('unread')) {
                        $userli.addClass('unread').find('.badge').hide();
                    }
                });
            }
        };
        var remove_unread_class = function ($userid) {
            $("li[data-userid='" + $userid + "']").removeClass('unread');
        };
        var reset_message_search = function () {
            collapse_tab();
            $("#allmessages div.conversationbar").addClass('hidden');
            $("#allmessages .message-contact-list .original").addClass('hidden');
            $("#allmessages .message-contact-list .searchlist").addClass('hidden');
            $("#allmessages div.newmessagebar").removeClass('hidden');
            $("#allmessages .message-contact-list .searchhelp").removeClass('hidden');
            $("#new-message-search input").val('').focus();
        };
        var reset_contact_list = function ($useridtoshow) {
            // remove the search contact list and display the original.
            // If we sent a message to a user from search, move them to the existing list.
            // If they were an existing user move them to the top of the list.
            $("#allmessages div.newmessagebar").addClass('hidden');
            $("#allmessages .message-contact-list .searchlist").addClass('hidden');
            $("#allmessages .message-contact-list .searchhelp").addClass('hidden');
            $("#allmessages div.conversationbar").removeClass('hidden');

            if ($useridtoshow) {
                var $originalli = $("#allmessages .message-contact-list .original li[data-userid=" + $useridtoshow + "]");
                if ($originalli.length) {
                    $originalli.find('.message-snippet').hide();
                    $originalli.parent().prepend($originalli);
                } else {
                    var $searchli = $("#allmessages .message-contact-list li[data-userid=" + $useridtoshow + "]");
                    $("#allmessages .original").prepend($searchli);
                }
                $('#allmessages .original li').removeClass('active');
                $('#allmessages .original li:first > a').trigger('click');
                $("#allmessages .message-contact-list .nocontactsormessages").addClass('hidden');
            } else if ($(window).width() > 768) { // Only auto show for tablet and greater
                var $orig = $('#allmessages .original li.active').removeClass('active');
                if (!$orig.length) {
                    $orig = $('#allmessages .original li:first');
                }
                $orig.children('a').trigger('click');
            }

            $("#allmessages .message-contact-list .original").removeClass('hidden');
        };

        var collapse_tab = function () {
            // On mobile view, link to collapse messages.
            // Bootstrap doesn't provide a way to "close" its tabs so we do it manually.
            var $activetab = $(".tab-pane.active, ul.nav-tabs li.active");
            var $userid = $activetab.data('userid');
            $activetab
                .removeClass('active')
                .end()
                .find('[data-toggle="tab"]')
                .attr('aria-expanded', false);
            $('body').removeClass('modal-open-mobile');
            remove_unread_class($userid);
        };
        var contact_search = function (q, cb) {
            var $divtoblock = $("div.message-contact-list");
            if (!$divtoblock.data('blockUI.isBlocked')) {
                $divtoblock.block({message: "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>"});
            }

            var $args = {
                searchstring: q
            };
            $ajax.call([{
                methodname: 'local_conversations_search_contacts',
                args: $args,
                done: function ($contactresults) {
                    var $resultsarr =
                        $.map($contactresults, function (value, index) {
                            return [value];
                        });
                    $divtoblock.unblock();
                    cb($resultsarr);
                },
                fail: ajax_fail
            }]);
        };

        var search_found = function ($usersearchlist) {
            // Use API to search for contacts & people i've received messages from.

            // With results, create a "search" contact list from template.
            var $promises = [];
            $promises.push(template.render('local_conversations/contact_list', {
                search: true,
                mycontactswithmessages: $usersearchlist,
                hascontacts: $usersearchlist.length
            }));
            // Also create conversation panels for returned users, in case they didnt have one previously.
            $promises = $promises.concat($usersearchlist.map(function ($usercontext) {
                return template.render('local_conversations/conversation_panel', $usercontext);
            }));

            $.when.apply($, $promises).done(function () {
                $.each(arguments, function ($idx, $args) { // Unknown number of promises.
                    if ($idx === 0) {
                        // first promise is contact list.
                        var $html = $args.constructor === Array ? $args[0] : $args; // If there were no results, args is not an array.
                        $("#allmessages .message-contact-list .original").addClass('hidden');

                        $("#allmessages .message-contact-list .searchlist").replaceWith($html).removeClass('hidden');
                        if ($("#new-message-search input").val().length >= 3) {
                            $("#allmessages .message-contact-list .searchhelp").addClass('hidden');
                        }
                    } else if ($args.constructor === Array) {
                        // >1 nth promise are new conversation panels.
                        var $tabexists = $("#user-" + $usersearchlist[$idx - 1].id + "-tab");
                        if ($tabexists.length === 0) {
                            // Add panel for users who don't exist.
                            $("#allmessages .tab-content").append($args[0]);
                        }
                    }
                });
                if ($(window).width() > 768) {
                    // For tablet and greater, auto display first contact in the search list.
                    $('#allmessages .searchlist a:first').tab('show');
                }

            });
        };
        var ajax_delete_conversation = function ($otheruserid) {
            var $args = {
                otheruserid: $otheruserid
            };
            $ajax.call([{
                methodname: 'local_conversations_delete_conversation',
                args: $args,
                done: function ($result) {
                    $('#new-message').appendTo($('body'));
                    $('.tab-pane.active').remove();
                    // Remove active other user conversation.
                    $('li.message-contact.active').remove();
                    // Select the first on the message-contact list
                    var $first = $('#allmessages .original li:first a');
                    if ($first.length === 0) {
                        // show no messages.
                        $("#allmessages .message-contact-list .nocontactsormessages").removeClass('hidden');
                    } else if ($(window).width() > 768) {
                        $first.trigger('click');
                    }
                },
                fail: ajax_fail
            }]);
        };
        $('#allmessages').on('click', '.delete-conversation', function (evt) {
            evt.preventDefault();
            var $conversation = $(this).parents('.tab-pane.active').find('div.conversation');
            // Empty conversation, don't do anything.
            if ($conversation.children().length === 0) {
                return;
            }
            var $modal = $('#confirm_delete_conversation_dialog');
            $modal.css('position', 'absolute');
            $modal.appendTo($conversation);

            $modal.modal({backdrop: true});
            $('.modal-backdrop.in').appendTo($conversation).css('position', 'absolute');
        });
        $('#allmessages').on('click', '#confirm_delete_conversation_dialog .btn.confirm-delete', function (evt) {
            evt.preventDefault();
            $('#confirm_delete_conversation_dialog').appendTo($('body'));
            // Delete messages $otheruserid in conversation.
            var $otheruserid = $('.message-contact.active').data('userid');
            ajax_delete_conversation($otheruserid);
        });

        return {
            initialise: function ($refresh_setting) {
                if (parseInt($refresh_setting) >= 1000) { // Minimum 1000 or we will flood ajax requests.
                    $conversation_refresh_time = $refresh_setting;
                }

                $('#allmessages .tab-content').on('click', '.panel-heading', function (evt) {
                    var $tgt = $(evt.target);
                    if ($tgt.is('.panel-heading, .fa-chevron-left') && $(window).width() < 768) {
                        collapse_tab();
                    }
                });

                $('#allmessages').on('show.bs.tab', 'a[data-toggle="tab"]', function (evt) {
                    // When we show a tab do this.
                    var $tgt = $(evt.target);
                    if ($(window).width() < 768) {
                        // For mobiles we display tab as a modal.
                        $('body').addClass('modal-open-mobile');
                    }

                    var $newmessageform = $('#new-message');
                    var $userid = $tgt.parents('li.message-contact').data('userid');
                    $newmessageform.find("input[name='useridto']").val($userid);

                    var $activetab = $($(evt.target).attr("href"));
                    clear_message_box();
                    var $activetabpanel = $('div.panel-body', $activetab).append($newmessageform);
                    $newmessageform.removeClass('hidden');
                    var $conversationscroll = $activetabpanel.find('.conversation');
                    $conversationscroll.scrollTop($conversationscroll.prop("scrollHeight"));
                    if (!$tgt.parents('ul.searchlist').length) {
                        var $refreshnow = true; // Refresh immediately or as callback after marking last panel read.
                        // remove "unread" class from previous tab
                        var $lasttab = $(evt.relatedTarget);
                        if ($lasttab.length) {
                            // Mark as read any new unread notifications that have come in through polling.
                            var $olduserid = $lasttab.parents('li.message-contact').data('userid');
                             var $oldpanelunreadids = $("#user-" + $olduserid + "-tab div.conversation .unread").map(function () {
                                return $(this).data('messageid');
                            }).toArray();
                             if ($oldpanelunreadids.length) {
                                $refreshnow = false;
                                mark_messages_read_by_id($oldpanelunreadids, function(){ refresh_conversation($userid); });
                            }
                            remove_unread_class($olduserid);
                        }
                        if($refreshnow === true) {
                            // Only do the following if not in a search.
                            refresh_conversation($userid);
                        }
                        var $newpanelunreadids = $("#user-" + $userid + "-tab div.conversation .unread").map(function () {
                            return $(this).data('messageid');
                        }).toArray();
                        // Mark this tab's messages as read ONLY when switching to the tab.
                        if ($newpanelunreadids.length) {
                            mark_messages_read_by_id($newpanelunreadids);
                        }

                        // Update browser url # to current user.
                        var $myhash = '#user-' + $userid;
                        if (history.replaceState) {
                            history.replaceState(null, null, $myhash);
                        }
                        else {
                            location.hash = $myhash;
                        }
                    }
                });

                // Submit a new message.
                $('#new-message .btn.submit').click(function () {
                    $(this).addClass('hidden');
                    $(this).parents('form').submit();
                }).parents('form')
                    .on('submit', function ($evt) {
                        /**
                         * Bind validation/submit event for new message
                         */
                        var $form = $(this);

                        var $message = $form.find("textarea[name='message']");
                        var $useridto = $form.find("input[name='useridto']");


                        if ($evt.isDefaultPrevented() === false) { // Validation has passed

                            send_message($useridto.val(), $message.val());
                            remove_unread_class($useridto.val());
                        }
                        $evt.preventDefault();

                    });

                // Make new message textbox autogrow.
                $("#new-message-edit").autoGrow().on('input propertychange change', function () {
                    // Make submit button appear when there is text.
                    var $btn = $(this).siblings("button.submit");
                    this.value.length ? $btn.removeClass('hidden') : $btn.addClass('hidden'); // jshint ignore:line
                });

                $("#allmessages button.newmessage").click(function () {
                    reset_message_search();
                    contact_search('', search_found);
                });
                $("#allmessages button.cancelnewmessage").click(function () {
                    reset_message_search();
                    reset_contact_list();
                });
                $("#allmessages button.searchall").click(function () {
                    var $searchstring = $("#new-message-search input").val();
                    contact_search($searchstring, search_found);
                });
                var locationhash = window.location.hash;
                if (locationhash) {
                    // If user hash is specified, show that tab by default
                    // We append the -tab to avoid the jump
                    $('#allmessages').find('.nav-tabs a[href="' + locationhash + '-tab"]').tab('show');
                } else if ($(window).width() > 768) {
                    // Show the first tab when not in mobile view.
                    $('#allmessages .nav-tabs a:first').tab('show');
                }

                // Using transform on pageload has bugs.
                // So Disable transitions/transforms until document loaded.
                document.body.className += " transform-enabled";

                $(window).on("hashchange", function () {
                    // If this causes the tab to show weird, its because someone linked directly to user-x-tab instead of user-x
                    var $newtab = $('#allmessages').find('.nav-tabs a[href=' + window.location.hash + '-tab]');
                    if ($newtab.length === 0) {
                        // reload the page because its from a new user.
                       location.reload();
                    }
                    $newtab.tab('show');
                });
                //setup before functions
                var typingTimer;                // timer identifier
                var doneTypingInterval = 750;  // time in ms, comfortable guestimate of time before searching (.75 seconds)
                // Bind search box, with delay in searching.
                $("#new-message-search input").on('input', function () {
                    clearTimeout(typingTimer);
                    var $searchstring = $(this).val();
                    var $divtoblock = $("div.message-contact-list");
                    if ($searchstring.length >= 3) {

                        if (!$divtoblock.data('blockUI.isBlocked')) {
                            $divtoblock.block({message: "<span class='glyphicon glyphicon-refresh glyphicon-refresh-animate spinning'></span>"});
                        }
                        if ($searchstring.length == 3) {
                            // immediate search on 3
                            contact_search($searchstring, search_found);
                        }
                        typingTimer = setTimeout(function () {
                            contact_search($searchstring, search_found);
                        }, doneTypingInterval);
                    } else if (!$searchstring) {
                        // Search is cleared
                        reset_message_search();
                        $divtoblock.unblock();
                    } else {
                        $divtoblock.unblock();
                    }
                });
            }
        };
    });
