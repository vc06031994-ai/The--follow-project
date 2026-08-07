<?php
require_once('wp-load.php');
$courses = get_posts(['post_type'=>'sfwd-courses', 'posts_per_page'=>1]);
if(!empty($courses)) {
    $course_id = $courses[0]->ID;
    $steps = get_post_meta($course_id, 'ld_course_steps', true);
    echo "--- ld_course_steps ---\n";
    print_r($steps['sections'] ?? 'no sections');
    echo "\n\n--- course_sections ---\n";
    print_r(get_post_meta($course_id, 'course_sections', true));
}
