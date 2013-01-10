<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array('all'=>get_string('allcourses', 'block_course_list'), 'own'=>get_string('owncourses', 'block_course_list'));

    $settings->add(new admin_setting_configselect('block_course_list_adminview', get_string('adminview', 'block_course_list'),
                       get_string('configadminview', 'block_course_list'), 'all', $options));

    $settings->add(new admin_setting_configcheckbox('block_course_list_hideallcourseslink', get_string('hideallcourseslink', 'block_course_list'),
                       get_string('confighideallcourseslink', 'block_course_list'), 0));

    $settings->add(new admin_setting_configcheckbox('block_course_list_showshortname', get_string('showshortnames', 'block_course_list'),
                       get_string('configshowshortname', 'block_course_list'), 0));

    $options = array('none'=>get_string('nocategory', 'block_course_list'), 'top'=>get_string('showtopcategory', 'block_course_list'), 'sub'=>get_string('showsubcategory', 'block_course_list'));

    $settings->add(new admin_setting_configselect('block_course_list_categoryview', get_string('categoryview', 'block_course_list'),
                       get_string('configcategoryview', 'block_course_list'), 'none', $options));

    $settings->add(new admin_setting_configcheckbox('block_course_list_showhiddencategories', get_string('showhiddencategories', 'block_course_list'),
                       get_string('configshowhiddencategories', 'block_course_list'), 0));
}


