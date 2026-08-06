<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [tfp_program_syllabus]
 * Renders a dynamic accordion based on LearnDash Course content (Section Headings and Lessons).
 * Designed for the WooCommerce Single Product page of a Program.
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

    // Get Course Steps (including Sections)
    $steps_data = get_post_meta($course_id, 'ld_course_steps', true);
    
    if (empty($steps_data['sections'])) {
        // Fallback: If no sections, just show Lessons as headers
        $lessons = learndash_get_lesson_list($course_id, ['num' => -1]);
        if (empty($lessons)) return '';
        
        ob_start();
        echo '<div class="tfp-syllabus-accordion">';
        foreach ($lessons as $index => $lesson) {
            tfp_render_syllabus_item($lesson->post_title, $lesson->post_content, $lesson->ID, $index === 0);
        }
        echo '</div>';
        echo tfp_syllabus_styles_and_scripts();
        return ob_get_clean();
    }

    // Main Logic: Sections as Headers, Lessons as Content list
    $sections = $steps_data['sections'];
    $h = $steps_data['h'] ?? []; // Hierarchy structure

    ob_start();
    ?>
    <div class="tfp-syllabus-accordion">
        <?php
        $index = 0;
        foreach ($sections as $section) {
            $index++;
            $active_class = ($index === 1) ? ' is-active' : '';
            $section_id = $section['id'];
            $section_name = $section['name'];
            
            // Collect lessons that belong to this section in the hierarchy
            $lessons_in_section = [];
            $found_section = false;
            
            // Loop through hierarchy to find lessons following this section until the next section
            foreach($h as $type => $steps) {
                if ($type !== 'sfwd-lessons') continue;
                
                // LearnDash 3.0 hierarchy handling
                foreach($steps as $step_id => $sub_steps) {
                    // Check if this step is part of the current section
                    // LD stores section info separately, so we check the order
                }
            }

            // A more reliable way for LD 3.0:
            // The hierarchy 'h' is a flat array where keys are step IDs.
            // Sections are also in that hierarchy.
            $flat_h = $h;
            $lessons_in_section = [];
            $start_collecting = false;
            
            foreach ($flat_h as $key => $sub) {
                // If we hit our target section ID
                if ($key == $section_id) {
                    $start_collecting = true;
                    continue;
                }
                
                // If we are collecting and hit ANOTHER section, stop
                if ($start_collecting) {
                    $is_other_section = false;
                    foreach ($sections as $s) {
                        if ($s['id'] == $key) {
                            $is_other_section = true;
                            break;
                        }
                    }
                    if ($is_other_section) break;
                    
                    // If it's a lesson, add to list
                    if (get_post_type($key) === 'sfwd-lessons') {
                        $lessons_in_section[] = get_post($key);
                    }
                }
            }
            ?>
            <div class="tfp-syllabus-item<?php echo $active_class; ?>">
                <button type="button" class="tfp-syllabus-header">
                    <span class="tfp-syllabus-title"><?php echo esc_html($section_name); ?></span>
                    <span class="tfp-syllabus-icon">
                        <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>
                <div class="tfp-syllabus-body">
                    <div class="tfp-syllabus-content">
                        <?php if (!empty($lessons_in_section)) : ?>
                            <ul class="tfp-syllabus-lessons-list">
                                <?php foreach ($lessons_in_section as $lesson) : ?>
                                    <li><?php echo esc_html($lesson->post_title); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p><?php esc_html_e('No lessons in this section.', 'tfp-dashboard'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
    echo tfp_syllabus_styles_and_scripts();
    return ob_get_clean();
});

function tfp_render_syllabus_item($title, $content, $lesson_id, $is_first = false) {
    $active_class = $is_first ? ' is-active' : '';
    ?>
    <div class="tfp-syllabus-item<?php echo $active_class; ?>">
        <button type="button" class="tfp-syllabus-header">
            <span class="tfp-syllabus-title"><?php echo esc_html($title); ?></span>
            <span class="tfp-syllabus-icon">
                <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </button>
        <div class="tfp-syllabus-body">
            <div class="tfp-syllabus-content">
                <ul class="tfp-syllabus-lessons-list">
                    <li><?php echo esc_html($title); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

function tfp_syllabus_styles_and_scripts() {
    ob_start();
    ?>
    <style>
        .tfp-syllabus-accordion {
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            overflow: hidden;
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
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #F9FAFB;
            border: none;
            cursor: pointer;
            text-align: left;
            font-weight: 600;
            font-size: 17px;
            color: #111827;
            transition: all 0.2s;
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
            margin-bottom: 12px;
            font-size: 15px;
            color: #4B5563;
            line-height: 1.4;
        }
        .tfp-syllabus-lessons-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 10px;
            width: 6px;
            height: 6px;
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
