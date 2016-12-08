/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'core/ajax', 'core/log', 'core/str', 'core/notification', 'mod_book/jquerypp.custom', 'mod_book/modernizr.custom', 'mod_book/jquery.bookblock'],
    function ($, $ajax, $log, $str, $notify) {
        return {
            initialise: function ($startpage, $animationSpeed) {
                var $bookblock = $('#bb-bookblock');
                var $booksection = $('div.book-section');
                var $popstate = false;
                var $toc;
                var $minheight = $bookblock.css('min-height');
                var $oldheight = $minheight;
                var $newheight = $minheight;
                var $resizetimeout;

                var resizeBook = function () {
                    // Adjust the book size if window is resized or expand/collapse is clicked.
                    clearTimeout($resizetimeout);
                    setTimeout(function(){
                        $bookblock.height($minheight);
                        $newheight = $bookblock[0].scrollHeight;
                        $bookblock.height($oldheight);
                        $bookblock.animate({'height': $newheight}, 100);
                    }, 500);
                };
                var updateToc = function (index) {
                    $toc = $("ul.toc li");
                    $toc.removeClass('active');
                    // Only update nav toc.
                    var $navtoc = $('.book-nav ul.toc li');
                    if ($navtoc.length == index + 1) {
                        $booksection.addClass('bb-end');
                    }
                    if (index < 0) {
                        $booksection.addClass('bb-start');
                        return;
                    }
                    // Update current toc link.
                    $navtoc.eq(index).addClass('active');
                    scrollToActiveToc();
                };
                var scrollToActiveToc = function () {
                    var $scrollTo = $('.book-nav ul.toc li.active');
                    var $scrollwrapper = $('.book-nav .dropdown.open .dropdown-menu ul');
                    if ($scrollTo.position() === undefined) {
                        return;
                    }
                    var $scrollwrapperstart = $scrollwrapper.scrollTop();
                    var $scrollwrapperend = $scrollwrapperstart + $scrollwrapper.height(); //end of wrapper
                    var $itempos = $scrollTo.position().top; // -8
                    if ($scrollTo && ($itempos < 0 || $scrollwrapperstart + $itempos > $scrollwrapperend)) {
                        // Scroll it into view if its not in view.
                        $scrollwrapper.animate({
                            scrollTop: $scrollwrapperstart - 10 + $itempos
                        }, 100);

                    }
                };
                var updateProgressBar = function () {
                    var progress_container = $('.page-progress-top .current-page'),
                        progress_text = $('.bb-item:visible .page-progress-bottom').html();

                    //Update Text
                    if (!!progress_text) {
                        progress_container.html(progress_text);
                    } else {
                        var totalpages = $('.book_content[data-chapterid="false"] .total').text();
                        progress_container.html('Page <span class="current">0</span> of <span class="total">' + totalpages + '</span>');
                    }

                    var bar = $('.page-progress-top .progress-bar'),
                        current = progress_container.find('.current').text(),
                        total = progress_container.find('.total').text(),
                        progress = Math.round(parseInt(current) / parseInt(total) * 100);

                    if (!progress || progress <= 0) {
                        bar.width(0);
                    } else {
                        bar.width(progress + '%');
                    }
                };
                var stopVideosOnFlip = function($pageitem) {
                    $pageitem.find('iframe').each(function(){
                        try {
                            // Force exception on all other players to ensure video stop.
                            if (this.src.indexOf('youtube.com/embed') == -1 ||
                                this.src.indexOf('enablejsapi=1') == -1

                            ) { // Not a Youtube video or can't control it.
                                throw "Exception";
                            }
                            // Pause Youtube video.
                            var $jsonPauseCommand = JSON.stringify({
                                'event': 'command',
                                'func': 'pauseVideo',
                                'args': []
                            });
                            this.contentWindow.postMessage($jsonPauseCommand, "*");
                        } catch (e) {
                            // Trigger video source reload.
                            this.src = this.src;
                        }
                    });
                };
                $bookblock.bookblock(
                    {
                        startPage: $startpage + 1,
                        speed: $animationSpeed,
                        onEndFlip: function (old, page, isLimit, isStart, isEnd) {
                            updateToc(page - 1);
                            stopVideosOnFlip($bookblock.find('.bb-item').eq(old));
                            if (isStart) {
                                $booksection.addClass('bb-start');
                            } else {
                                $booksection.removeClass('bb-start');
                            }
                            if (isEnd) {
                                $booksection.addClass('bb-end');
                            } else {
                                $booksection.removeClass('bb-end');
                            }
                            /*
                             Height hack to get minimum height of content.
                             */
                            $bookblock.height($minheight);
                            $newheight = $bookblock[0].scrollHeight;
                            $bookblock.height($oldheight);

                            if (($oldheight > $newheight) || ($(window).scrollTop() > $newheight )) {
                                $('html, body').animate({
                                    scrollTop: $('div[role=main]').offset().top - 15
                                }, 300);
                            }
                            updateScrollPosition($('.book-nav ul.toc'));
                            //Update page numbers
                            updateProgressBar();
                            $bookblock.animate({'height': $newheight}, 100);
                            $oldheight = $newheight;
                            /*
                             update new page index, and send read.
                             * */
                            if ($popstate) {
                                // If the page flipped due to back button, don't update history.
                                $popstate = false;
                            } else {
                                /* Update browser history and url, with new ajax page information */

                                var $bookid = $bookblock.data('bookid');
                                var $pageitem = $bookblock.find('.bb-item').eq(page);
                                var $chapterid = $pageitem.data('chapterid');
                                document.title = $pageitem.data('chaptertitle');

                                // Trigger book view events.
                                $ajax.call([{
                                    methodname: 'mod_book_view_book',
                                    args: {bookid: $bookid, chapterid: $chapterid},
                                    done: function ($disablereturn) {
                                    }
                                }]);

                                // Strip &chapterid=xxxx from the current url.
                                var $new_param = '';
                                if ($chapterid) {
                                    $new_param = '&chapterid=' + $chapterid;
                                }
                                // remove existing
                                var $new_url = window.location.href.replace(/\&chapterid[\=a-z0-9]*/i, '');

                                // Must put new param before #
                                // there is always a bookid, so we dont need to look for "?"
                                var $url_anchor = $new_url.split("#")[1];
                                if ($url_anchor !== undefined) {
                                    $url_anchor = '#' + $url_anchor;
                                } else {
                                    $url_anchor = '';
                                }
                                var $full_url = $new_url.split("#")[0] + $new_param + $url_anchor;
                                window.history.pushState({page: page}, "", $full_url);
                            }
                        },
                        onLoaded: function () {
                            // Set initial height on load.
                            $bookblock.height($bookblock[0].scrollHeight);
                            updateProgressBar();
                        }
                    }
                );
                $(window).on("popstate", function (event) {
                    $popstate = true;
                    if (!event.originalEvent.state) {
                        $bookblock.bookblock('jump', $startpage);
                    } else {
                        $bookblock.bookblock('jump', event.originalEvent.state.page + 1);
                    }
                });
                $(window).on("resize", resizeBook);
                $('#region-main .toggle').on("click", resizeBook);

                $bookblock.on('swipeleft', function () {
                    $(this).bookblock('next');
                    return false;
                }).on('swiperight', function () {
                    $(this).bookblock('prev');
                    return false;
                });
                $('.book-nav').on('click touchstart', ".bb-btn", function (event) {
                    var target = event.currentTarget;
                    event.preventDefault();
                    switch (target.id) {
                        case 'bb-nav-next':
                            $bookblock.bookblock('next');
                            event.stopPropagation();
                            break;
                        case 'bb-nav-prev':
                            $bookblock.bookblock('prev');
                            event.stopPropagation();
                            break;
                        case 'bb-nav-first':
                            $bookblock.bookblock('first');
                            break;
                        case 'bb-nav-last':
                            $bookblock.bookblock('last');
                            break;
                    }

                });

                // On table of contents click, we need to change page.
                $toc = $("ul.toc li > a");
                $toc.on('click touchstart', function (event) {
                    event.preventDefault();
                    // There can be multiple toc's, get the index only from this one.
                    var $idx = $(this).parents('ul.toc').find('li > a').index($(this));
                    $bookblock.bookblock('jump', $idx + 2); // 0 is now index page.
                    return false; // Prevent toc from closing.
                });
                $(".book-nav ul.toc").on('scroll', function () {
                    updateScrollPosition($(this));
                });
                var updateScrollPosition = function (scrollWrap) {
                    var $noscroll = scrollWrap[0].scrollHeight === undefined;
                    // Detect scroll to bottom or scroll to top.
                    if ($noscroll || (scrollWrap.scrollTop() + scrollWrap.innerHeight() >= scrollWrap[0].scrollHeight)) {
                        scrollWrap.addClass('scroll-limit-bottom');
                    } else {
                        scrollWrap.removeClass('scroll-limit-bottom');
                    }
                    // Detect scroll to bottom or scroll to top.
                    if ($noscroll || scrollWrap.scrollTop() < 6) {
                        scrollWrap.addClass('scroll-limit-top');
                    } else {
                        scrollWrap.removeClass('scroll-limit-top');
                    }
                };
                $('.book-nav .dropdown').on('shown.bs.dropdown', function () {
                    updateScrollPosition($('.book-nav ul.toc'));
                    scrollToActiveToc();
                });
                updateToc($startpage - 1);
            }
        };
    });
