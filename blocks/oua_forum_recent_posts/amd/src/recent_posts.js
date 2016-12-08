/**
 * Handle recent discussion links and/or inline posting
 *
 * @module     block_oua_forum_recent_posts/recent_posts
 * @package    block_oua_forum_recent_posts
 * @copyright  2016 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'core/ajax', 'core/log', 'core/str', 'core/templates',
            'core/notification', 'core/event', 'block_oua_forum_recent_posts/validator', 'block_oua_forum_recent_posts/jquery.blockUI'],
    function ($, ajax, log, str, templates, notification, event, validator, blockui) {
        var $data;
        /**
         * Ajax load more discussions based on perpage parameter
         * @param onTemplateLoaded , callback function to execute on success
         */
        var ajax_get_more_discussions = function (onTemplateLoaded) {
            $data = $("div.disucssion-list-head").data();
            $("div.discussionloading").show();

            $('.discussion-list-body').on('click', 'div.wrapper a', function(e){
                e.stopPropagation();
                e.cancelBubble = true;
            });
            
            ajax.call([{
                methodname: 'block_oua_forum_get_discussions',
                args: {
                    forumid: $data.forumid,
                    page: $data.page,
                    perpage: $data.perpage
                },
                done: function ($getdiscussionreturn) {
                    load_discussion_template($getdiscussionreturn.discussions, onTemplateLoaded);
                },
                fail: ajax_fail
            }]);
        };
        /*
         On ajax failure display a log back in message
         */
        var ajax_fail = function ($ex) {
            $("div.discussionloading").hide();
            $("div.block_oua_forum_recent_posts a.showmore").hide();
            str.get_strings([
                {key: 'error'},
                {key: 'loggedout', component: 'block_oua_forum_recent_posts'},
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
        /**
         * Merge discussions into the template for display, call the callback on success
         * @param $discussions discussion context for template
         * @param onTemplateLoaded callback function to execute
         */
        var load_discussion_template = function ($discussions, onTemplateLoaded) {
            var templatePromise = templates.render('block_oua_forum_recent_posts/discussions', {discussions: $discussions});
            templatePromise.done(function (source, javascript) {
                $("div.discussion-list-body").append(source);
                var newSource = $("div.discussion-recent");
                event.notifyFilterContentUpdated(newSource);

                $data.page++;
                if ($discussions.length < $data.perpage) {
                    $("div.block_oua_forum_recent_posts a.showmore").hide();
                } else {
                    $("div.block_oua_forum_recent_posts a.showmore").show();
                }
                if (typeof onTemplateLoaded === 'function') {
                    onTemplateLoaded($discussions);
                }
            });
        };


        /**
         * Function to execute on new discussion success
         * keeps loading discussions until it finds the new discussion.
         * @param $discussionid
         */
        var new_discussion_added = function ($discussionid) {
            // Keep loading discussions until our new discussion appears.
            clear_discussion_body();
            ajax_get_more_discussions(function ($discussions) {
                load_discussions_until_id($discussionid, $discussions);
            });
        };
        var update_discussion = function($discussionid, $postid) {
            ajax.call([{
                methodname: 'block_oua_forum_get_discussion_by_id',
                args: {
                    discussionid: $discussionid
                },
                done: function ($getdiscussionreturn) {
                    var $maindivblock = $("div.block_oua_forum_recent_posts");
                    var $replypost = $('#reply-post');
                    $replypost.collapse('hide');
                    $replypost.appendTo($maindivblock);
                    var templatePromise = templates.render('block_oua_forum_recent_posts/discussions', {discussions: $getdiscussionreturn.discussion, expanded: true, newpostconfirm: true});
                    templatePromise.done(function (source, javascript) {
                        templates.replaceNode('div.discussion-topic[data-discussionid="' + $discussionid + '"]', source, javascript);
                    });
                    var $newpost = $('div.discussion-post[data-postid="' + $postid + '"]');
                    // Show the success div above the new post.
                    var $successmsg = $("div.discussion-reply.alert-success").prependTo($newpost).show();
                    var $scrolloffset = $successmsg.offset().top - 200;

                    $('html, body').animate({
                        scrollTop: $scrolloffset
                    }, 250);
                    setTimeout(function(){
                        // Hide the success message after 5 seconds.
                        $("div.discussion-reply.alert-success").slideUp(400, function() {
                          $(this).prependTo($maindivblock);
                        });
                    }, 3000);
                },
                fail: ajax_fail
            }]);
        };
        /**
         *  Clear the discussion list, to load new discussions.
         */
        var clear_discussion_body = function () {
            $data = $("div.disucssion-list-head").data('page', 0);
            var $maindivblock = $("div.block_oua_forum_recent_posts");
            var $modal = $('#confirm_delete_post_dialog');
            $modal.modal('hide');
            $modal.appendTo($maindivblock);
            var $replypost = $('#reply-post');
            $replypost.collapse('hide');
            $replypost.appendTo($maindivblock);
            $("div.discussion-list-body").html('');
        };
        /**
         * Quasi recursive function, keeps loading discussions from forum
         * until the discussion id is found.
         * @param $discussionid
         * @param $discussions
         */
        var load_discussions_until_id = function ($discussionid, $discussions) {
            var $found = false;
            $.each($discussions, function (key, val) {
                if (val.discussion == $discussionid) {
                    $found = true;
                }
            });
            if (!$found && $discussions.length !== 0) {
                ajax_get_more_discussions(function ($newdiscussions) {
                    load_discussions_until_id($discussionid, $newdiscussions);
                });
            } else {
                $('div.discussion-topic[data-discussionid="' + $discussionid + '"] .collapse').collapse('show');
                hide_loading_div();
            }
        };
        /**
         * Temp storage to save post in case of error.
         * @param $neworreply
         * @param $forumid
         * @param $subject
         * @param $message
         */
        var temp_store_forum_post = function ($neworreply, $forumid, $subject, $message) {
            localStorage.setItem('forum_post_temp_save_subject_' + $neworreply + '_' + $forumid, $subject);
            localStorage.setItem('forum_post_temp_save_message_' + $neworreply + '_' + $forumid, $message);
        };
        var clear_editor_content = function ($textareaid) {
            $('#' + $textareaid + 'editable').html('');
            $('#' + $textareaid).val('');
        };
        /**
         * Clean temp save post
         * @param $neworreply 'new' or 'reply' depending on post type
         * @param $forumid
         */
        var temp_store_delete_forum_post = function ($neworreply, $forumid) {
            localStorage.removeItem('forum_post_temp_save_subject_' + $neworreply + '_' + $forumid);
            localStorage.removeItem('forum_post_temp_save_message_' + $neworreply + '_' + $forumid);
        };
        /**
         * Function to hide the loading icon, used as a callback for template loaded.
         */
        var hide_loading_div = function () {
            $("div.discussionloading").hide();
        };
        /**
         * Bind events for the bootstrap modal for a new discussion topic
         */
        $('#new-post').on('show.bs.collapse', function ($evt) {
            var $btn = $($evt.relatedTarget); // Button that triggered the modal
            var $forumid = $btn.data('forumid'); // Extract info from data-* attributes
            var $form = $(this).parents('form');
            if (localStorage !== 'undefined') {
                // Load previous message if found in local storage
                $form.find("input[name='subject']").val(localStorage['forum_post_temp_save_subject_new_' + $forumid]);
                $form.find("textarea[name='message']").val(localStorage['forum_post_temp_save_message_new_' + $forumid]);
            }
            $("div.block_oua_forum_recent_posts .alert").hide(); // Hide previous alert messages
        }).on('click', '.cancel', function ($evt) {
            // On cancel remove local storage and reset form validation.
            var $form = $(this).parents('form');
            var $forumid = $form.data('forumid');
            $form.find("input[name='subject']").val('');
            $form.find("textarea[name='message']").val('');
            clear_editor_content('new-post-edit-message');
            temp_store_delete_forum_post('new', $forumid);
            $(this).parents('form').validator('reset');
        }).on('click', '.submit', function ($evt) {
            // Submit form on button click
            $(this).parents('form').submit();
        }).find('form').validator().on('submit', function ($evt) {
            /**
             * Bind validation/submit event for new discussion topic
             */
            /* update to use a serialize -> json function/library? */
            var $form = $(this);

            var $subject = $form.find("input[name='subject']");
            var $message = $form.find("textarea[name='message']");
            var $inlineattachmentsid  = $form.find("input[name='inlineattachmentsid']");
            var $args = {
                forumid: $data.forumid,
                subject: $subject.val(),
                message: $message.val(),
                options: [{name: 'inlineattachmentsid', value: $inlineattachmentsid.val()}]
            };
            if (localStorage !== 'undefined') {
                // Save form data in local storage in case of
                temp_store_forum_post('new', $args.forumid, $args.subject, $args.message);
            }

            if ($evt.isDefaultPrevented() === false) { // Validation has passed
                ajax.call([{
                    methodname: 'block_oua_forum_add_discussion',
                    args: $args,
                    done: function ($newdiscussionreturn) {
                        $subject.val(''); // Clear/reset form
                        $message.val('');
                        clear_editor_content('new-post-edit-message');
                        $('#new-post').collapse('hide'); // hide modal
                        if ($newdiscussionreturn.warnings.length === 0) {
                            if (localStorage !== 'undefined') {
                                // Clear local storage.
                                temp_store_delete_forum_post('new', $args.forumid);
                            }
                            $("div.discussion-posted.alert-success").show();
                            new_discussion_added($newdiscussionreturn.discussionid);
                        } else {
                            $("div.discussion-posted.alert-warning").show();
                        }
                    },
                    fail: ajax_fail
                }]);
            }
            $evt.preventDefault();

        });
        /**
         * Bind events for the bootstrap modal for a new discussion topic
         */
        $('#reply-post').on('show.bs.collapse', function ($evt) {
            var $form = $(this).parents('form');
            var $postid = $form.data('postid');
            if (localStorage !== 'undefined') {
                // Load previous message if found in local storage
                $form.find("textarea[name='message']").val(localStorage['forum_post_temp_save_message_reply_'  + $postid]);
            }
            $("div.block_oua_forum_recent_posts .alert").hide(); // Hide previous alert messages
        }).on('click', '.cancel', function ($submitevt) {
            var $form = $(this).parents('form');
            // On cancel remove local storage and reset form validation.
            var $postid = $form.data('postid');
            temp_store_delete_forum_post('reply', $postid);
            $form.find("input[name='subject']").val('');
            $form.find("textarea[name='message']").val('');
            clear_editor_content('reply-post-edit-message');
            $(this).parents('form').validator('reset');
        }).on('click', '.submit', function ($evt) {
            // Submit form on button click
            $(this).parents('form').submit();
        }).find('form').validator().on('submit', function ($evt) {
            /**
             * Bind validation/submit event for new discussion topic
             */
            /* update to use a serialize -> json function/library? */
            var $form = $(this);
            var $parentid = $form.parents('.discussion-post').data('postid');

            var $subject = $form.find("input[name='subject']");
            var $message = $form.find("textarea[name='message']");
            var $inlineattachmentsid = $form.find("input[name='inlineattachmentsid']");
            var $args = {
                postid: $parentid,
                subject: $subject.val(),
                message: $message.val(),
                options: [{name: 'inlineattachmentsid', value: $inlineattachmentsid.val()}]
            };
            if (localStorage !== 'undefined') {
                // Save form data in local storage in case of
                temp_store_forum_post('reply', $args.postid, $args.subject, $args.message);
            }

            if ($evt.isDefaultPrevented() === false) { // Validation has passed
                ajax.call([{
                    methodname: 'block_oua_forum_add_discussion_post',
                    args: $args,
                    done: function ($newdiscussionreturn) {
                        $subject.val(''); // Clear/reset form
                        $message.val('');
                        clear_editor_content('reply-post-edit-message');
                        $('#reply-post').collapse('hide'); // hide modal
                        if ($newdiscussionreturn.warnings.length === 0) {
                            if (localStorage !== 'undefined') {
                                // Clear local storage.
                                temp_store_delete_forum_post('reply', $args.postid);
                            }
                            update_discussion($newdiscussionreturn.discussionid, $newdiscussionreturn.postid);
                        } else {
                            $("div.discussion-reply.alert-warning").show();
                        }
                    },
                    fail: ajax_fail
                }]);
            }
            $evt.preventDefault();

        });

        var ajax_delete_post = function($postid) {
            $("div.block_oua_forum_recent_posts .alert").hide();
            ajax.call([{
                methodname: 'block_oua_forum_delete_discussion_post',
                args: {
                    postid: $postid
                },
                done: function ($postdeletereturn) {
                    $("div.discussion-deleted.alert-success").show();
                    clear_discussion_body();
                    ajax_get_more_discussions(hide_loading_div);
                },
                fail: ajax_fail
            }]);
        };
        var deletePostClick = function($evt) {
            $evt.preventDefault();
            var $post = $(this).parents('.discussion-post');
            var $modal = $('#confirm_delete_post_dialog');
            $modal.parents('.discussion-post').removeClass('modal-delete-on');
            $modal.appendTo($post);
            $post.addClass('modal-delete-on');
            var $postid = $post.data('postid');
            $modal.modal({backdrop:true}).on('hide.bs.modal', function($evt){
                $(this).parents('.discussion-post').removeClass('modal-delete-on');
            });
            $('.confirm-delete', $modal).data('postid', $postid);
            $('.modal-backdrop.in').appendTo($post);
            $('body').removeClass('modal-open');
        };
        var replyPostClick = function($evt) {
            $evt.preventDefault();
            var $post = $(this).parents('.discussion-post');
            var $replyformdiv = $('#reply-post');
            var $discussiontitle = $post.parents('.discussion-topic').find('.panel-title .subject').text();
            // Hardcode replies.
            $replyformdiv.find("input[name='subject']").val('Re: ' + $discussiontitle).attr('readonly',true);
            $replyformdiv.appendTo($post);
            $replyformdiv.collapse('show');
        };
        var confirmDeleteClick = function ($evt) {
            var $postid = $(this).data('postid');
            $('#confirm_delete_post_dialog').modal('hide');
            ajax_delete_post($postid);
        };
        var showMoreClick = function($evt) {
            $evt.preventDefault();
            ajax_get_more_discussions(hide_loading_div);
            return false;
        };

        /*
         Bind the different click events for showmore, delete/reply
         */
        $("div.block_oua_forum_recent_posts")
            .on('click', 'a.showmore', showMoreClick)
            .on('click', '.deletepost a', deletePostClick)
            .on('click', '.discussionreply a', replyPostClick)
            .on('click', '.confirm-delete', confirmDeleteClick);

        return {
            // Expose initialisation function.
            initialise: function () {
                ajax_get_more_discussions(hide_loading_div);
            }
        };
    });
