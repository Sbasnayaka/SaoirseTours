<?php
class PageBuilder
{

    // Render an entire section by ID
    public static function renderSection($section, $pdo)
    {
        // 1. Fetch Elements
        $stmt = $pdo->prepare("SELECT * FROM section_elements WHERE section_id = ? ORDER BY display_order ASC");
        $stmt->execute([$section['id']]);
        $elements = $stmt->fetchAll();

        // 2. Generate Section Styles
        $style = "";
        $style .= "padding-top: " . ($section['padding_top'] ?? '50px') . "; ";
        $style .= "padding-bottom: " . ($section['padding_bottom'] ?? '50px') . "; ";
        $style .= "min-height: " . ($section['min_height'] ?? 'auto') . "; ";
        $style .= "color: " . ($section['text_color'] ?? '#000000') . "; ";

        // Background Logic
        if (($section['bg_type'] ?? 'color') === 'image' && !empty($section['image'])) {
            $style .= "background: url('uploads/" . htmlspecialchars($section['image']) . "') center/cover no-repeat; ";
        } elseif (($section['bg_type'] ?? 'color') === 'gradient' && !empty($section['bg_gradient'])) {
            $style .= "background: " . $section['bg_gradient'] . "; ";
        } else {
            $style .= "background-color: " . ($section['bg_color'] ?? '#ffffff') . "; ";
        }

        // 3. Render Wrapper
        echo '<section id="sec-' . $section['id'] . '" class="builder-section ' . htmlspecialchars($section['custom_css_class'] ?? '') . '" style="' . $style . '">';
        echo '<div class="container">';

        // Layout Wrapper (if full width vs contained, current assumes container)
        // If layout is 'full-width' from previous schema, we might remove container class, but keeping simple for now.

        echo '<div class="row">';
        echo '<div class="col-12">'; // Currently single column stack for v1

        if (empty($elements) && !empty($section['content'])) {
            // Backward compatibility: Render legacy content if no elements exist
            echo $section['content'];
        }

        foreach ($elements as $el) {
            self::renderElement($el);
        }

        echo '</div>'; // End Col
        echo '</div>'; // End Row
        echo '</div>'; // End Container
        echo '</section>';
    }

    // Render individual element
    private static function renderElement($el)
    {
        $settings = json_decode($el['settings'], true) ?? [];
        $style = self::generateElementStyle($settings);
        $content = $el['content']; // Don't escape yet, might be HTML from editor

        switch ($el['type']) {
            case 'heading':
                $tag = $settings['tag'] ?? 'h2';
                echo "<$tag class='builder-heading' style='$style'>" . htmlspecialchars($content) . "</$tag>";
                break;

            case 'text':
                echo "<div class='builder-text' style='$style'>$content</div>";
                break;

            case 'image':
                $imgWidth = $settings['width'] ?? '100%';
                echo "<div class='builder-image-wrap' style='text-align:" . ($settings['align'] ?? 'left') . "'>";
                echo "<img src='uploads/" . htmlspecialchars($content) . "' style='max-width: $imgWidth; height: auto; border-radius: " . ($settings['border_radius'] ?? 0) . "px; $style' alt=''>";
                echo "</div>";
                break;

            case 'button':
                $btnClass = 'btn ' . ($settings['style'] ?? 'btn-primary');
                $url = $settings['url'] ?? '#';
                echo "<div class='builder-btn-wrap' style='text-align:" . ($settings['align'] ?? 'left') . "'>";
                echo "<a href='" . htmlspecialchars($url) . "' class='$btnClass' style='$style'>" . htmlspecialchars($content) . "</a>";
                echo "</div>";
                break;

            case 'spacer':
                $height = $settings['height'] ?? '20px';
                echo "<div style='height: $height;'></div>";
                break;

            case 'divider':
                echo "<hr style='border-color: " . ($settings['color'] ?? '#ccc') . "; $style'>";
                break;
        }
    }

    private static function generateElementStyle($settings)
    {
        $css = "";
        if (!empty($settings['font_size']))
            $css .= "font-size: " . $settings['font_size'] . "; ";
        if (!empty($settings['color']))
            $css .= "color: " . $settings['color'] . "; ";
        if (!empty($settings['margin_top']))
            $css .= "margin-top: " . $settings['margin_top'] . "; ";
        if (!empty($settings['margin_bottom']))
            $css .= "margin-bottom: " . $settings['margin_bottom'] . "; ";
        if (!empty($settings['font_weight']))
            $css .= "font-weight: " . $settings['font_weight'] . "; ";
        return $css;
    }
}
?>