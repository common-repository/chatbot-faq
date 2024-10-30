<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function chatbot_faq_load_public_assets() {
    if (!is_admin()) {
        wp_enqueue_style('chatbot-style', plugins_url('chatbot-faq-style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('chatbot-script', plugins_url('chatbot-faq-public-script.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_enqueue_script('chatbot-admin-script', plugins_url('../admin/chatbot-faq-script.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    $faq_design_data = get_option('chatbot_faq_design_data', array(
        'question_bg_color' => '#ffffff',
        'question_text_color' => '#000000',
        'answer_bg_color' => '#f0f0f0',
        'answer_text_color' => '#000000',
        'title_bg_color' => '#ffffff',
        'title_text_color' => '#000000',
        'chat_width_desktop' => '25',
        'chat_width_mobile' => '80',
        'icon' => 'black-right.png',
        'custom_icon' => ''
    ));

    // Generate CSS inline
    $custom_css = "
    a:where(:not(.wp-element-button)) {
        color: #0069ff;
        text-decoration: none; 
    }
    a:where(:not(.wp-element-button)):hover {
        color: #7ed6ff;
        text-decoration: underline;
    }
    a:where(:not(.wp-element-button)):visited {
        color: #da8f2d;
    }
    a:where(:not(.wp-element-button)):active {
        color: #a33919; /
    } 
    #close-chatbot.sticky {
    color: " . esc_attr($faq_design_data['title_text_color']) . ";
    }
    .chatbot-question {
        background-color: " . esc_attr($faq_design_data['question_bg_color']) . ";
        color: " . esc_attr($faq_design_data['question_text_color']) . ";
    }
    .chatbot-answer {
        background-color: " . esc_attr($faq_design_data['answer_bg_color']) . ";
        color: " . esc_attr($faq_design_data['answer_text_color']) . ";
    }
    .sticky-wrapper {
        background-color: " . esc_attr($faq_design_data['title_bg_color']) . ";
        color: " . esc_attr($faq_design_data['title_text_color']) . ";
        position: -webkit-sticky; /* Safari */
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    #chatbot-faq h2 {
        background-color: " . esc_attr($faq_design_data['title_bg_color']) . ";
        color: " . esc_attr($faq_design_data['title_text_color']) . ";
    }
    @media (min-width: 768px) {
        #chatbot-faq {
            width: " . esc_attr($faq_design_data['chat_width_desktop']) . "%;
        }
    }
    @media (max-width: 767px) {
        #chatbot-faq {
            width: " . esc_attr($faq_design_data['chat_width_mobile']) . "%;
        }
    }";
    wp_add_inline_style('chatbot-style', wp_kses_post($custom_css));

    // Add sticky title
    $faq_data = get_option('chatbot_faq_data', array(
        'sticky_title' => false,
    ));
    $sticky_title = isset($faq_data['sticky_title']) ? $faq_data['sticky_title'] : false;
    wp_add_inline_script('chatbot-script', 'var chatbot_faq_sticky_title = ' . wp_json_encode($sticky_title) . ';', 'before');
}

add_action('wp_enqueue_scripts', 'chatbot_faq_load_public_assets');

function chatbot_faq_display_icon() {
    $faq_data = get_option('chatbot_faq_data', array(
        'active' => false,
        'title' => 'Chatbot FAQ',
        'sticky_title' => false,
    ));
    $faq_design_data = get_option('chatbot_faq_design_data', array(
        'icon' => '',
        'custom_icon' => ''
    ));

    if (!$faq_data['active']) {
        return;
    }

    $sticky_title = isset($faq_data['sticky_title']) ? $faq_data['sticky_title'] : false;
    $icon_url = !empty($faq_design_data['custom_icon']) ? esc_url($faq_design_data['custom_icon']) : esc_url(plugin_dir_url(__FILE__) . 'icons/' . $faq_design_data['icon']);
    ?>
    <div id="chatbot-icon-wrapper">
        <img src="<?php echo esc_url($icon_url); ?>" id="chatbot-icon" alt="<?php echo esc_attr_e('Chatbot Icon', 'your-text-domain'); ?>">

        <div id="chatbot-faq">
            <div class="<?php echo esc_attr(($sticky_title) ? 'sticky-wrapper' : ''); ?>">
                <h2><?php echo esc_html($faq_data['title']); ?></h2>
                <button id="close-chatbot" class="<?php echo esc_attr(($sticky_title) ? 'sticky' : ''); ?>" style="display: none;">
                    X
                </button>
            </div>
            <?php echo do_shortcode('[chatbot_faq]'); ?>
        </div>
    </div>
    <?php
}

add_action('wp_footer', 'chatbot_faq_display_icon');

function chatbot_faq_render_faq() {
    $faq_data = get_option('chatbot_faq_data', array('title' => 'Chatbot FAQ', 'questions' => array()));
    $title = isset($faq_data['title']) ? sanitize_text_field($faq_data['title']) : 'Chatbot FAQ';
    $questions = isset($faq_data['questions']) ? $faq_data['questions'] : array();

    if (!function_exists('convert_markdown_to_html')) {
        require_once plugin_dir_path(__FILE__) . '/Parsedown.php'; 

        function convert_markdown_to_html($text) {
            $Parsedown = new Parsedown();
            return $Parsedown->text($text);
        }
    }

    ob_start();
    ?>
    <ul class="chatbot-faq-list clearfix">
        <?php foreach ($questions as $index => $faq) : ?>
            <li class="chatbot-question">
                <span class="chatbot-text">
                    <?php echo wp_kses_post(convert_markdown_to_html($faq['question'])); ?>
                </span>
            </li>
            <li class="chatbot-answer">
                <span class="chatbot-text">
                    <?php echo wp_kses_post(convert_markdown_to_html($faq['answer'])); ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php
    return ob_get_clean();
}

add_shortcode('chatbot_faq', 'chatbot_faq_render_faq');
