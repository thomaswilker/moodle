define(['jquery', 'core/config'], function ($, $mdlcfg) {
        $("div.block_message_broadcast").delegate("a.dismiss", "click", function (event) {
            var $messagebox, $messageid;
            event.preventDefault();

            $messagebox = $(this).parent(); //Main Block
            $messageid = $(this).next("span.messageid").text();

            var $messageWrapper = $(this).parents('.message-notification'); //Visible message box with wrapper div.


            $.ajax({
                /* not proper moodle ajax way, but using for now */
                'url': $mdlcfg.wwwroot + '/blocks/message_broadcast/ajax.php?action=dismissmessage',
                'dataType': 'json',
                data: {
                    'action': 'dismissmessage',
                    'messageid': $messageid
                },
                success: function (data) {
                    if (data.success === true) {
                        //Add collapsed class to the div to kickoff transition post ajax
                        $messageWrapper.addClass('collapsed');
                    }
                }
            });
        });


    return {
        mbinitialise: function () {
            // console.log('mbinitial');
            //Attach heights to all message notifications to facilitate animations
            $('.message-notification').each(function(){
                var $height = $(this).height();
                $(this).height($height);
            });
        }
    };
});
