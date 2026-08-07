<?php
require_once('wp-load.php');
$c = get_posts(['post_type'=>'sfwd-courses', 'posts_per_page'=>1]);
if(!empty($c)) {
    print_r(get_post_meta($c[0]->ID));
    
    // Also try to get lessons directly
    if (function_exists('learndash_get_course_lessons_list')) {
        $lessons = learndash_get_course_lessons_list($c[0]->ID);
        echo "Lessons: \n";
        print_r($lessons);
    }
    
    if (function_exists('learndash_course_get_steps_by_type')) {
        echo "Steps: \n";
        $steps = learndash_course_get_steps_by_type($c[0]->ID, 'sfwd-lessons');
        print_r($steps);
    }
}
