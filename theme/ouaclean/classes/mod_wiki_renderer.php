<?php
include_once($CFG->dirroot . "/mod/wiki/renderer.php");

class theme_ouaclean_mod_wiki_renderer extends mod_wiki_renderer {
    public function tabs($page, $tabitems, $options) {
        global $USER;

        $tabs = array();
        $context = context_module::instance($this->page->cm->id);

        $pageid = null;
        if (!empty($page)) {
            $pageid = $page->id;
        }

        $selected = $options['activetab'];

        // make specific tab linked even it is active
        if (!empty($options['linkedwhenactive'])) {
            $linked = $options['linkedwhenactive'];
        } else {
            $linked = '';
        }

        if (!empty($options['inactivetabs'])) {
            $inactive = $options['inactivetabs'];
        } else {
            $inactive = array();
        }
        if (has_capability('mod/wiki:createpage', $context)) {
            $userid = 0;
            if ($this->page->activityrecord->wikimode == 'individual') {
                $userid = $USER->id;
            }
            if (!$gid = groups_get_activity_group($this->page->cm)) {
                $gid = 0;
            }

            if ($subwiki = wiki_get_subwiki_by_group($this->page->cm->instance, $gid, $userid)) {
                $swid = $subwiki->id;
                $link = new moodle_url('/mod/wiki/create.php', array('action' => 'new', 'swid' => $swid));
                $tabs[] = new tabobject('new',   $link, get_string('newpage', 'wiki'));
            }
        }

        foreach ($tabitems as $tab) {
            if ($tab == 'edit' && !has_capability('mod/wiki:editpage', $context)) {
                continue;
            }
            if ($tab == 'comments' && !has_capability('mod/wiki:viewcomment', $context)) {
                continue;
            }
            if ($tab == 'files' && !has_capability('mod/wiki:viewpage', $context)) {
                continue;
            }
            if (($tab == 'view' || $tab == 'map' || $tab == 'history') && !has_capability('mod/wiki:viewpage', $context)) {
                continue;
            }
            if ($tab == 'admin' && !has_capability('mod/wiki:managewiki', $context)) {
                continue;
            }
            $link = new moodle_url('/mod/wiki/'. $tab. '.php', array('pageid' => $pageid));
            if ($linked == $tab) {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'wiki'), '', true);
            } else {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'wiki'));
            }
        }

        return $this->tabtree($tabs, $selected, $inactive);
    }
}