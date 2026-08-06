<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [tfp_program_syllabus]
 * Renders a dynamic accordion based on LearnDash Course Sections and Lessons.
 */
add_shortcode('tfp_program_syllabus', function($atts) {
    $product_id = get_the_ID();
    
    // Debugging check (Only for admins if needed)
    $is_admin = current_user_can('manage_options');

    if (!$product_id || get_post_type($product_id) !== 'product') {
        if (isset($_GET['test_product_id'])) {
            $product_id = (int)$_GET['test_product_id'];
        } else {
            return '';
        }
    }

    // 1. Get the Course ID linked to this Product
    $course_id = 0;
    $related_courses = get_post_meta($product_id, '_related_course', true);
    
    if (is_array($related_courses)) {
        $course_id = (int) reset($related_courses);
    } elseif (!empty($related_courses)) {
        $course_id = (int) $related_courses;
    }

    if (!$course_id) {
        return $is_admin ? '<p>Debug: No LearnDash Course linked to this Product ID ' . $product_id . '</p>' : '';
    }

    // 2. Fetch Lessons using standard LearnDash function
    $lessons = learndash_get_lesson_list($course_id, ['num' => -1]);
    
    if (empty($lessons)) {
        return '<p>' . esc_html__('Course content coming soon.', 'tfp-dashboard') . '</p>';
    }

    // 3. Try to get Sections (LearnDash 3.0+)
    $sections = [];
    $steps_data = get_post_meta($course_id, 'ld_course_steps', true);
    if (!empty($steps_data['sections'])) {
        $sections = $steps_data['sections'];
    }

    // 4. Group Lessons by Sections
    $grouped_content = [];
    
    if (empty($sections)) {
        // Fallback: Group everything under a default heading if no sections exist
        $grouped_content[__('Course Syllabus', 'tfp-dashboard')] = $lessons;
    } else {
        // Grouping logic based on after_id
        $current_section = __('Introduction', 'tfp-dashboard');
        
        // Initial section check (if one starts at the very beginning)
        foreach ($sections as $s) {
            if ($s['after_id'] == 0) {
                $current_section = $s['name'];
                break;
            }
        }
        
        $grouped_content[$current_section] = [];
        
        foreach ($lessons as $lesson) {
            $grouped_content[$current_section][] = $lesson;
            
            // Does a new section start after this lesson?
            foreach ($sections as $s) {
                if ($s['after_id'] == $lesson->ID) {
                    $current_section = $s['name'];
                    if (!isset($grouped_content[$current_section])) {
                        $grouped_content[$current_section] = [];
                    }
                }
            }
        }
    }

    ob_start();
    ?>
    <div class="tfp-syllabus-accordion">
        <?php
        $i = 0;
        foreach ($grouped_content as $section_title => $section_lessons) :
            if (empty($section_lessons)) continue;
            $i++;
            $active_class = ($i === 1) ? ' is-active' : '';
            ?>
            <div class="tfp-syllabus-item<?php echo $active_class; ?>">
                <button type="button" class="tfp-syllabus-header">
                    <span class="tfp-syllabus-title"><?php echo esc_html($section_title); ?></span>
                    <span class="tfp-syllabus-icon">
                        <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>
                <div class="tfp-syllabus-body">
                    <div class="tfp-syllabus-content">
                        <ul class="tfp-syllabus-lessons-list">
                            <?php foreach ($section_lessons as $lesson) : ?>
                                <li><?php echo esc_html($lesson->post_title); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    echo tfp_syllabus_styles_and_scripts();
    return ob_get_clean();
});

function tfp_syllabus_styles_and_scripts() {
    ob_start();
    ?>
    <style>
        .tfp-syllabus-accordion {
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .tfp-syllabus-item {
            border-bottom: 1px solid #E5E7EB;
        }
        .tfp-syllabus-item:last-child {
            border-bottom: none;
        }
        .tfp-syllabus-header {
            width: 100%;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #F9FAFB;
            border: none;
            cursor: pointer;
            text-align: left;
            font-weight: 700;
            font-size: 16px;
            color: #111827;
            transition: all 0.2s;
            text-transform: uppercase;
        }
        .tfp-syllabus-header:hover {
            background: #F3F4F6;
        }
        .tfp-syllabus-item.is-active .tfp-syllabus-header {
            background: #fff;
            border-bottom: 1px solid #F3F4F6;
        }
        .tfp-syllabus-icon {
            transition: transform 0.3s;
            color: #D4AF37;
        }
        .tfp-syllabus-item.is-active .tfp-syllabus-icon {
            transform: rotate(180deg);
        }
        .tfp-syllabus-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: #fff;
        }
        .tfp-syllabus-item.is-active .tfp-syllabus-body {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }
        .tfp-syllabus-content {
            padding: 20px 24px;
        }
        .tfp-syllabus-lessons-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .tfp-syllabus-lessons-list li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 10px;
            font-size: 15px;
            color: #4B5563;
        }
        .tfp-syllabus-lessons-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 9px;
            width: 6px;
            height: 6px;
            background: #D4AF37;
            border-radius: 50%;
        }
    </style>
    <script>
        document.querySelectorAll('.tfp-syllabus-header').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                item.classList.toggle('is-active');
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
