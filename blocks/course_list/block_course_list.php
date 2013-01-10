<?php

include_once($CFG->dirroot . '/course/lib.php');

class block_course_list extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_course_list');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $icon  = '<img src="' . $OUTPUT->pix_url('i/course') . '" class="icon" alt="" />';

        $adminseesall = true;
        if (isset($CFG->block_course_list_adminview)) {
           if ( $CFG->block_course_list_adminview == 'own'){
               $adminseesall = false;
           }
        }

        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {    // Just print My Courses
            if ($courses = enrol_get_my_courses(NULL, 'sortorder ASC, fullname ASC')) {
        		$this->get_block_course_list($courses);

                $this->title = get_string('mycourses');
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_hideallcourseslink)) {
                    $this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a> ...";
                }
            }
            $this->get_remote_courses();
            if ($this->content->items) { // make sure we don't return an empty list
                return $this->content;
            }
        }

        $categories = get_categories("0");  // Parent = 0   ie top-level categories only
        if ($categories) {   //Check we have categories
            if (count($categories) > 1 || (count($categories) == 1 && $DB->count_records('course') > 200)) {     // Just print top level category links
                foreach ($categories as $category) {
                    $categoryname = format_string($category->name, true, array('context' => context_coursecat::instance($category->id)));
                    $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[]="<a $linkcss href=\"$CFG->wwwroot/course/category.php?id=$category->id\">".$icon . $categoryname . "</a>";
                }
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_hideallcourseslink)) {
                    $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                }
                $this->title = get_string('categories');
            } else {                          // Just print course names of single category
                $category = array_shift($categories);
                $courses = get_courses($category->id);

                if ($courses) {
                    foreach ($courses as $course) {
                        $coursecontext = context_course::instance($course->id);
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                        $this->content->items[]="<a $linkcss title=\""
                                   . format_string($course->shortname, true, array('context' => $coursecontext))."\" ".
                                   "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">"
                                   .$icon. format_string($course->fullname, true, array('context' => context_course::instance($course->id))) . "</a>";
                    }
                /// If we can update any course of the view all isn't hidden, show the view all courses link
                    if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_hideallcourseslink)) {
                        $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                    }
                    $this->get_remote_courses();
                } else {

                    $this->content->icons[] = '';
                    $this->content->items[] = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', context_coursecat::instance($category->id))) {
                        $this->content->footer = '<a href="'.$CFG->wwwroot.'/course/edit.php?category='.$category->id.'">'.get_string("addnewcourse").'</a> ...';
                    }
                    $this->get_remote_courses();
                }
                $this->title = get_string('courses');
            }
        }

        return $this->content;
    }

    function get_remote_courses() {
        global $CFG, $USER, $OUTPUT;

        if (!is_enabled_auth('mnet')) {
            // no need to query anything remote related
            return;
        }

        $icon = '<img src="'.$OUTPUT->pix_url('i/mnethost') . '" class="icon" alt="" />';

        // shortcut - the rest is only for logged in users!
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses','mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id);
                $this->content->items[]="<a title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$course->hostid}&amp;wantsurl=/course/view.php?id={$course->remoteid}\">"
                    .$icon. format_string($course->fullname) . "</a>";
            }
            // if we listed courses, we are done
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotehosts', 'mnet');
            $this->content->icons[] = '';
            foreach($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'].get_string('courseson','mnet').'<a title="'.$somehost['name'].'" href="'.$somehost['url'].'">'.$icon.$somehost['name'].'</a>';
            }
            // if we listed hosts, done
            return true;
        }

        return false;
    }

    /**
     * Returns the role that best describes the course list block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }

    function get_block_course_list($courses) {
        global $CFG, $OUTPUT;
        $icon  = '<img src="' . $OUTPUT->pix_url('i/course') . '" class="icon" alt="" />&nbsp;';
	
	    if (empty($CFG->block_course_list_categoryview) || $CFG->block_course_list_categoryview == "none") {
            foreach ($courses as $course) {
                $coursedisplay = (empty($CFG->block_course_list_showshortname)) ? $course->fullname : get_string('courseextendednamedisplay', '', $course);
        
	            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
	            $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
	            $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
	                       "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".$icon.$coursedisplay. "</a>";
            }
	    } elseif($CFG->block_course_list_categoryview == "top") {
	        $prevcatid = ""; // keeps previous category

            foreach ($courses as $course) {
                $category = get_course_category($course->category);
                
                $i = 0; // prevent possible infinite loop
                while ($category->parent != 0 && $i < 10) {
                    $category = get_course_category($category->parent);
                    $i++;
                }

                if ($category->id != $prevcatid) {
                    $catcontext = context_coursecat::instance($category->id);
                    if ($category->visible || has_capability('moodle/category:viewhiddencategories', $catcontext) || !empty($CFG->block_course_list_showhiddencategories)) {
                    	$this->content->items[] = "<h2>".$category->name."</h2>";
                    } else {
                        $this->content->items[] = "<h2>".str_repeat("-",30)."</h2>";
                    }
                }

        	    $coursedisplay = empty($CFG->block_course_list_showshortname) ? $course->fullname : get_string('courseextendednamedisplay', '', $course);

                $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
                $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                           "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".$icon.$coursedisplay. "</a>";

                $prevcatid = $category->id;
            }
        } elseif($CFG->block_course_list_categoryview == "sub") { //This option is very ugly and could use some reworking if anyone has a better way
            $data = array();
            $temp = array();
            foreach ($courses as $course) {
                $temp = get_course_category($course->category);
                if (!isset($data[$temp->parent]) && $temp->parent != 0) {
                    $path = array_filter(explode("/", $temp->path));
                    $previd = 0;
                    foreach ($path as $p) {
                        if (!isset($data[$p])) {
                            $temp = get_course_category($p);
                            $data[$p]["category"] = $temp;
                            $data[$p]["courses"] = array();
                        }
                        $previd = $p;
                    }
                }
                $data[$course->category]["category"] = $temp;
                $data[$course->category]["courses"][] = $course;
            }

            $prevcat = array();
            $currcat = array();
            $courselist = "";
            foreach ($data as $key => $arr) {
                $currcat = $arr["category"];

                $catcontext = context_coursecat::instance($currcat->id);

                    if (!empty($prevcat)) {
                        if ($currcat->parent == 0) {
                            $courselist .= "</ul>\n";
                        }
                        if ($currcat->depth > $prevcat->depth) {
                            $courselist .= "<li>\n";
                        } elseif ($currcat->parent == 0 && $currcat->depth < $prevcat->depth) {
                            for ($i = $currcat->depth; $i < $prevcat->depth; $i++) {
                                $courselist .= "</li>\n</ul>\n";
                            }
                        } elseif ($currcat->depth < $prevcat->depth) {
                            $courselist .= "</ul></li>\n</ul>\n";
                        }
                    }
                if ($currcat->visible || has_capability('moodle/category:viewhiddencategories', $catcontext) || !empty($CFG->block_course_list_showhiddencategories)) {
                    $courselist .= "<ul>\n<li><h2>".$currcat->name."</h2></li>\n";
                } else {
                    $courselist .= "<ul>\n<li><h2>".str_repeat("-",30)."</h2></li>\n"; //have to show something for cat heading or view gets messed up
                }
                $prevcat = $currcat;

                foreach ($arr["courses"] as $course) {
                    $coursedisplay = empty($CFG->block_course_list_showshortname) ? $course->fullname : get_string('courseextendednamedisplay', '', $course);

                    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    $courselist .= "<li><a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".$icon.$coursedisplay. "</a></li>";
                }

            }
            $courselist .= "</ul>\n</li>\n</ul>\n";
            $this->content->items[] = $courselist;
        }
    }
}


