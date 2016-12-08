/**
 * Handle the chevron changes on slideup/down n notification and messages block
 *
 * @module     theme_ouaclean/chevron_slider
 * @package    theme_ouaclean
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery'], function($) {
        $("div.ouamsg.accordion .panel-title a").click(function(evt) {
            var $icon = $(this).find(".expand-collapse");
            if($(this).hasClass('collapsed')){
                $icon.toggleClass("fa-chevron-down fa-chevron-up");
            }
            else{
                $icon.toggleClass("fa-chevron-up fa-chevron-down");
            }
        });
        // This module does not expose anything.
        return {};
});
