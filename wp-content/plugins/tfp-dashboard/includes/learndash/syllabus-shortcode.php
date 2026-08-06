<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [tfp_program_syllabus]
 * Renders a dynamic accordion based on LearnDash Course Sections and Lessons.
 */
add_shortcode('tfp_program_syllabus', function($atts) {
    $product_id = get_the_ID();
    if (!$product_id || get_post_type($product_id) !== 'product') {
        if (isset($_GET['test_product_id'])) {
            $product_id = (int)$_GET['test_product_id'];
        } else {
            return '';
        }
    }

    $related_courses = get_post_meta($product_id, '_related_course', true);
    $course_id = 0;
    if (is_array($related_courses)) {
        $course_id = (int) reset($related_courses);
    } elseif (!empty($related_courses)) {
        $course_id = (int) $related_courses;
    }

    if (!$course_id) {
        return '<p>' . esc_html__('No course syllabus available.', 'tfp-dashboard') . '</p>';
    }

    // Get Course Steps from LearnDash Meta
    $steps_data = get_post_meta($course_id, 'ld_course_steps', true);
    
    // Fallback if no sections or steps found
    if (empty($steps_data['steps']['sfwd-lessons'])) {
        return '<p>' . esc_html__('Course content coming soon.', 'tfp-dashboard') . '</p>';
    }

    $lesson_ids = array_keys($steps_data['steps']['sfwd-lessons']);
    $sections = !empty($steps_data['sections']) ? $steps_data['sections'] : [];

    // Grouping Logic: Interleave Sections and Lessons
    $grouped_content = [];
    $current_section_name = __('General', 'tfp-dashboard');
    
    // If a section is at the very top (after_id = 0)
    foreach ($sections as $s) {
        if ($s['after_id'] == 0) {
            $current_section_name = $s['name'];
            break;
        }
    }
    
    $grouped_content[$current_section_name] = [];

    foreach ($lesson_ids as $lesson_id) {
        $grouped_content[$current_section_name][] = get_the_title($lesson_id);
        
        // Check if a new section starts AFTER this lesson
        foreach ($sections as $s) {
            if ($s['after_id'] == $lesson_id) {
                $current_section_name = $s['name'];
                if (!isset($grouped_content[$current_section_name])) {
                    $grouped_content[$current_section_name] = [];
                }
            }
        }
    }

    ob_start();
    ?>
    <div class="tfp-syllabus-accordion">
        <?php
        $i = 0;
        foreach ($grouped_content as $section_title => $lessons) :
            if (empty($lessons)) continue;
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
                            <?php foreach ($lessons as $lesson_title) : ?>
                                <li><?php echo esc_html($lesson_title); ?></li>
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
            margin-bottom: 30px;
            font-family: inherit;
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
            font-size: 18px;
            color: #111827;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            color: #D4AF37; /* Gold color */
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
            padding: 24px 32px;
        }
        .tfp-syllabus-lessons-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .tfp-syllabus-lessons-list li {
            position: relative;
            padding-left: 24px;
            margin-bottom: 14px;
            font-size: 16px;
            color: #4B5563;
            line-height: 1.5;
            font-weight: 500;
        }
        .tfp-syllabus-lessons-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 8px;
            width: 7px;
            height: 7px;
            background: #D4AF37; /* Gold dot */
            border-radius: 50%;
        }
        .tfp-syllabus-lessons-list li:last-child {
            margin-bottom: 0;
        }
    </style>
    <script>
        document.querySelectorAll('.tfp-syllabus-header').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                const isActive = item.classList.contains('is-active');
                if (isActive) {
                    item.classList.remove('is-active');
                } else {
                    item.classList.add('is-active');
                }
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
