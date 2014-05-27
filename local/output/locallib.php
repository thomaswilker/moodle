<?php
/**
 * This file contains only helper methods and classes.
 */

/**
 * Class course_list
 *
 * A helper class used to get an array of courses or to get an array of course links.
 */
class course_list {

    protected $courses = null;

    public function get_courses() {
        if ($this->courses === null) {
            $this->courses = get_courses('all', 'c.fullname');
        }
        return $this->courses;
    }

    public function get_course_links() {
        $courses = $this->get_courses();
        $links = array();
        foreach ($courses as $course) {
            $links[] = new action_link(
                new moodle_url('/course/view.php', array('id' => $course->id)),
                format_string($course->fullname, true, context_course::instance($course->id))
            );
        }
        return $links;
    }
}

/* This renderable would be defined in core - this is just a prototype */
class core_menu implements renderable {
    public $items;
    public $flags;

    const MENU_ITEM_ACTIVE = 1;
    const MENU_ITEM_DISABLED = 2;

    public function __construct($items) {
        $this->items = $items;
        $this->flags = array_pad(array(), count($items), 0);
    }

    public function set_menu_item_flags($index, $flags) {
        $this->flags[$index] = $flags;
    }
}

/* This renderable would be defined in core - this is just a prototype */
class core_menu_dropdown extends core_menu {
    public $button;

    public function __construct($button, $items) {
        $this->button = $button;
        parent::__construct($items);
    }
}

/* This renderable would be defined in core - this is just a prototype */
class core_list implements renderable {
    public $items;

    public function __construct($items) {
        $this->items = $items;
    }
}

class dom_utils {
    /**
     * Utility function to parse HTML into a dom structure, then add a set of attributes to the
     * first node of a specific type and return the modified html. This should sit in core somewhere.
     * @param $type string The type of node to search for
     * @param $attributes array The list of attributes to set. Class attribute will be appended, all
     *                          other attributes will be overwritten
     * @param $html string The html to parse and modify
     * @return string|boolean Returns false if the node was not found,
     *                        or there was an error parsing the HTML.
     *                        Otherwise returns the modified html.
     */
    public static function add_attributes_to_first_node_of_type($type, $attributes, $html) {
        $dom = new DOMDocument();
        $newhtml = '';
        try {
            $dom->loadHTML($html);
            $nodes = $dom->getElementsByTagName($type);
            if ($nodes->length <= 0) {
                // No nodes of that type existed in the html.
                return false;
            }
            $firstnode = $nodes->item(0);
            foreach ($attributes as $name => $value) {
                if ($name === 'class') {
                    // Special case - we don't want to overwrite any existing classes.
                    $existingclasses = $firstnode->getAttribute('class');
                    $classlist = explode(' ', $existingclasses);
                    $classlist = array_merge($classlist, explode(' ', $value));
                    $firstnode->setAttribute('class', implode(' ', $classlist));
                } else {
                    $firstnode->setAttribute($name, $value);
                }
            }
            $newhtml = $dom->saveHtml();
        } catch (Exception $e) {
            // Handle any parsing errors and return false.
            return false;
        }
        return $newhtml;
    }
}
