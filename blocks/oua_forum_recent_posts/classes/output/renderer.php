<?php
namespace block_oua_forum_recent_posts\output;

defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;
use moodle_url;
use ArrayIterator;
use stdClass;
use mod_forum_post_form;
use context_user;

class renderer extends plugin_renderer_base {
    public function display_forum_posts($cmforum, $discussions, $studentcount, $perpage, $canmanageforum = false) {
        global $CFG;

        if (!$discussions) {
            $discussions = array();
        }
        $data = new ArrayIterator($discussions);
        $nameddata = new stdClass();
        $nameddata->discussions = $data;
        $nameddata->linktoforum = new moodle_url('/mod/forum/view.php', array('f' => $cmforum->id));
        $nameddata->linktonewpost = new moodle_url('/mod/forum/post.php', array('forum' => $cmforum->id));
        $nameddata->studentcount = $studentcount;
        $nameddata->forumid = $cmforum->id;
        $nameddata->perpage = $perpage;
        $nameddata->canmanageforum = $canmanageforum;
        $nameddata->maxeditminute = round($CFG->maxeditingtime / 60); // time in minutes

        // Only support atto at this stage. See history for tinymce4 implementation, tinymce3 comes with moodle.
        // Proper use is $editor = editors_get_preferred_editor(null) , But we would have to implement the tinymce3 ajax manipulaitons.
        $editor = get_texteditor('atto');
        $newposteditordraftitemid = file_get_submitted_draft_itemid('message_newpost'); // Always 0;
        file_prepare_draft_area($newposteditordraftitemid, $cmforum->context->id, 'mod_forum', 'post', null, mod_forum_post_form::attachment_options($cmforum));

        $replyposteditordraftitemid = file_get_submitted_draft_itemid('message_replypost'); // Always 0;
        file_prepare_draft_area($replyposteditordraftitemid, $cmforum->context->id, 'mod_forum', 'post', null, mod_forum_post_form::attachment_options($cmforum));

        $options = array();
        $options['trusttext'] = false;
        $options['forcehttps'] = false;
        $options['subdirs'] = false;
        $options['maxfiles'] = -1;
        $options['context'] = $cmforum->context;
        $options['changeformat'] = 0;
        $options['noclean'] = false;
        $options['autosave'] = false;
        $options = array_merge($options, mod_forum_post_form::attachment_options($cmforum) );

        if ($options['maxfiles'] != 0 ) {
            $fpoptionsnew = $this->initialise_file_pickers($cmforum, $newposteditordraftitemid, $options['maxbytes']);
            $fpoptionsreply = $this->initialise_file_pickers($cmforum, $replyposteditordraftitemid, $options['maxbytes']);
        }

        /* @var $editor atto_texteditor */
        $editor->use_editor('new-post-edit-message', $options, $fpoptionsnew);
        $editor->use_editor('reply-post-edit-message', $options, $fpoptionsreply);

        $nameddata->newpostitemid = $newposteditordraftitemid;
        $nameddata->replypostitemid = $replyposteditordraftitemid;

        $html = $this->render_from_template('block_oua_forum_recent_posts/recent_posts', $nameddata);

        return $html;
    }

    private function initialise_file_pickers($cmforum, $draftareaid, $maxbytes) {
            $args = new stdClass();
            // need these three to filter repositories list
            $args->accepted_types = array('web_image');
            $args->return_types = (FILE_INTERNAL | FILE_EXTERNAL);
            $args->context =  $cmforum->context;
            $args->env = 'filepicker';
            // advimage plugin
            $image_options = initialise_filepicker($args);
            $image_options->context =  $cmforum->context;
            $image_options->client_id = uniqid();
            $image_options->maxbytes = $maxbytes;
            $image_options->env = 'editor';
            $image_options->itemid = $draftareaid;

            // moodlemedia plugin
            $args->accepted_types = array('video', 'audio');
            $media_options = initialise_filepicker($args);
            $media_options->context =  $cmforum->context;
            $media_options->client_id = uniqid();
            $media_options->maxbytes  = $maxbytes;
            $media_options->env = 'editor';
            $media_options->itemid = $draftareaid;

            // advlink plugin
            $args->accepted_types = '*';
            $link_options = initialise_filepicker($args);
            $link_options->context =  $cmforum->context;
            $link_options->client_id = uniqid();
            $link_options->maxbytes  = $maxbytes;
            $link_options->env = 'editor';
            $link_options->itemid = $draftareaid;

            $fpoptions['image'] = $image_options;
            $fpoptions['media'] = $media_options;
            $fpoptions['link'] = $link_options;

            return $fpoptions;
    }
}

