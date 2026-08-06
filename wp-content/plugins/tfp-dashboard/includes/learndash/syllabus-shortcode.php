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

    $course_id = 0;
    $related_courses = get_post_meta($product_id, '_related_course', true);
    if (is_array($related_courses)) {
        $course_id = (int) reset($related_courses);
    } elseif (!empty($related_courses)) {
        $course_id = (int) $related_courses;
    }

    if (!$course_id) return '';

    // Get Course Steps & Sections
    $steps_data = get_post_meta($course_id, 'ld_course_steps', true);
    $lessons = learndash_get_lesson_list($course_id, ['num' => -1]);
    
    if (empty($lessons)) return '';

    // Grouping logic
    $sections = !empty($steps_data['sections']) ? $steps_data['sections'] : [];
    $grouped = [];

    if (empty($sections)) {
        $grouped['Course Syllabus'] = $lessons;
    } else {
        // Find the section that starts at the beginning (after_id = 0)
        $current_section = 'General';
        foreach ($sections as $s) {
            if ($s['after_id'] == 0) {
                $current_section = $s['name'];
                break;
            }
        }
        
        $grouped[$current_section] = [];
        
        foreach ($lessons as $lesson) {
            $grouped[$current_section][] = $lesson;
            
            // If a new section starts after this lesson
            foreach ($sections as $s) {
                if ($s['after_id'] == $lesson->ID) {
                    $current_section = $s['name'];
                    if (!isset($grouped[$current_section])) {
                        $grouped[$current_section] = [];
                    }
                }
            }
        }
    }

    ob_start();
    ?>
    <div class="tfp-syllabus-accordion-container">
        <?php foreach ($grouped as $section_title => $section_lessons) : 
            if (empty($section_lessons)) continue; ?>
            <div class="tfp-syllabus-item">
                <button type="button" class="tfp-syllabus-header">
                    <span class="tfp-syllabus-title"><?php echo esc_html($section_title); ?></span>
                    <span class="tfp-syllabus-icon">
                        <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                </button>
                <div class="tfp-syllabus-body">
                    <div class="tfp-syllabus-content">
                        <ul class="tfp-syllabus-list">
                            <?php foreach ($section_lessons as $lesson) : ?>
                                <li><?php echo esc_html($lesson->post_title); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <style>
        .tfp-syllabus-accordion-container {
            display: flex;
            flex-direction: column;
            gap: 8px; /* Gap between accordion boxes */
            margin-top: 20px;
        }
        .tfp-syllabus-item {
            border: 1px solid #E0E0E0;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }
        .tfp-syllabus-header {
            width: 100%;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #F1F1F1; /* Figma Light Gray */
            border: none;
            cursor: pointer;
            text-align: left;
            font-weight: 600;
            font-size: 16px;
            color: #333;
            transition: background 0.2s;
        }
        .tfp-syllabus-header:hover {
            background: #E8E8E8;
        }
        .tfp-syllabus-icon {
            transition: transform 0.3s ease;
            color: #666;
        }
        .tfp-syllabus-item.is-active .tfp-syllabus-icon {
            transform: rotate(180deg);
        }
        .tfp-syllabus-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }
        .tfp-syllabus-item.is-active .tfp-syllabus-body {
            max-height: 1000px;
        }
        .tfp-syllabus-content {
            padding: 20px;
            border-top: 1px solid #E0E0E0;
        }
        .tfp-syllabus-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .tfp-syllabus-list li {
            padding: 8px 0;
            font-size: 15px;
            color: #555;
            border-bottom: 1px solid #F5F5F5;
        }
        .tfp-syllabus-list li:last-child {
            border-bottom: none;
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
});
