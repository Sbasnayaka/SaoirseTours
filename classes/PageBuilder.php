<?php
class PageBuilder
{

    // Render Section
    public static function renderSection($section, $pdo)
    {
        $stmt = $pdo->prepare("SELECT * FROM section_elements WHERE section_id = ? ORDER BY display_order ASC");
        $stmt->execute([$section['id']]);
        $elements = $stmt->fetchAll();

        // Section Styles
        $style = self::generateStyle([
            'padding_top' => $section['padding_top'],
            'padding_bottom' => $section['padding_bottom'],
            'min_height' => $section['min_height'],
            'color' => $section['text_color'] ?? 'inherit',
            'bg_color' => ($section['bg_type'] != 'image' && $section['bg_type'] != 'gradient') ? $section['bg_color'] : null,
            'bg_gradient' => ($section['bg_type'] == 'gradient') ? $section['bg_gradient'] : null
        ]);

        $bgImage = '';
        if (($section['bg_type'] ?? 'color') === 'image' && !empty($section['image'])) {
            $style .= "background-image: url('uploads/" . htmlspecialchars($section['image']) . "'); background-size: cover; background-position: center; ";
        }

        echo '<section id="sec-' . $section['id'] . '" class="builder-section position-relative ' . htmlspecialchars($section['custom_css_class'] ?? '') . '" style="' . $style . '">';

        // Overlay for readability if image bg
        if (($section['bg_type'] ?? 'color') === 'image') {
            echo '<div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: 0.3; z-index: 0;"></div>';
        }

        echo '<div class="container position-relative" style="z-index: 1;">';
        echo '<div class="row">';
        echo '<div class="col-12">';

        if (empty($elements) && !empty($section['content'])) {
            echo $section['content'];
        }

        foreach ($elements as $el) {
            self::renderElement($el);
        }

        echo '</div></div></div></section>';
    }

    // Render Element
    private static function renderElement($el)
    {
        $s = json_decode($el['settings'], true) ?? [];
        $style = self::generateStyle($s);
        $content = $el['content'];

        // Common wrapper for alignment/margin
        $wrapperStyle = "text-align: " . ($s['text_align'] ?? 'left') . "; margin-bottom: " . ($s['margin_bottom'] ?? '20px') . "; margin-top: " . ($s['margin_top'] ?? '0') . ";";

        echo "<div class='element-wrapper element-" . $el['type'] . "' style='$wrapperStyle'>";

        switch ($el['type']) {
            case 'heading':
                $tag = $s['tag'] ?? 'h2';
                echo "<$tag style='$style'>" . htmlspecialchars($content) . "</$tag>";
                break;

            case 'text':
                echo "<div style='$style'>$content</div>";
                break;

            case 'image':
                $width = $s['width'] ?? '100%';
                $imgStyle = $style . "max-width: $width; height: auto;";
                $src = strpos($content, 'http') === 0 ? $content : "uploads/" . htmlspecialchars($content);
                echo "<img src='$src' style='$imgStyle' alt='Element Image'>";
                break;

            case 'button':
                $btnClass = 'btn ' . ($s['style'] ?? 'btn-primary') . ' ' . ($s['size'] ?? '');
                $url = $s['url'] ?? '#';
                echo "<a href='" . htmlspecialchars($url) . "' class='$btnClass' style='$style'>" . htmlspecialchars($content) . "</a>";
                break;

            case 'icon':
                $iconSize = $s['font_size'] ?? '2rem';
                $iconColor = $s['color'] ?? 'var(--primary-color)';
                echo "<i class='bi " . htmlspecialchars($content) . "' style='font-size: $iconSize; color: $iconColor; $style'></i>";
                break;

            case 'video':
                // Expects YouTube URL or plain URL
                $videoUrl = $content;
                if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                    // Simple Youtube convert
                    $videoUrl = str_replace("watch?v=", "embed/", $videoUrl);
                    echo "<div class='ratio ratio-16x9' style='max-width: " . ($s['width'] ?? '100%') . "; margin: 0 auto; $style'><iframe src='$videoUrl' allowfullscreen></iframe></div>";
                } else {
                    echo "<video src='$videoUrl' controls style='max-width: 100%; $style'></video>";
                }
                break;

            case 'card':
                // Composite Content: Image (if any) + Title + Text
                // Content format: JSON or just ignore? Let's use stored content text as body.
                // Image comes from settings 'card_image' if we want.
                // Simplified: Content is Body Text. Settings has Title.
                echo "<div class='card h-100 shadow-sm' style='$style'>";
                if (!empty($s['card_image'])) {
                    echo "<img src='uploads/" . htmlspecialchars($s['card_image']) . "' class='card-img-top' style='height: 200px; object-fit: cover;'>";
                }
                echo "<div class='card-body'>";
                if (!empty($s['card_title']))
                    echo "<h5 class='card-title'>" . htmlspecialchars($s['card_title']) . "</h5>";
                echo "<div class='card-text'>$content</div>";
                if (!empty($s['card_btn_text']))
                    echo "<a href='" . ($s['card_btn_url'] ?? '#') . "' class='btn btn-primary mt-3'>" . htmlspecialchars($s['card_btn_text']) . "</a>";
                echo "</div></div>";
                break;

            case 'spacer':
                $height = $s['height'] ?? '50px';
                echo "<div style='height: $height;'></div>";
                break;

            case 'divider':
                echo "<hr style='border-color: " . ($s['color'] ?? '#dee2e6') . "; opacity: 1; margin: 0; $style'>";
                break;

            case 'list':
                // Content is newline separated items
                $items = explode("\n", $content);
                $listType = ($s['list_type'] ?? 'ul') == 'ol' ? 'ol' : 'ul';
                echo "<$listType style='$style'>";
                foreach ($items as $item) {
                    if (trim($item))
                        echo "<li>" . htmlspecialchars($item) . "</li>";
                }
                echo "</$listType>";
                break;
        }
        echo "</div>";
    }

    private static function generateStyle($s)
    {
        $css = "";
        $props = [
            'padding',
            'padding_top',
            'padding_bottom',
            'padding_left',
            'padding_right',
            'margin',
            'margin_top',
            'margin_bottom',
            'margin_left',
            'margin_right',
            'color',
            'background_color',
            'font_size',
            'font_weight',
            'line_height',
            'letter_spacing',
            'border_radius',
            'border',
            'box_shadow',
            'min_height',
            'bg_gradient',
            'opacity'
        ];

        foreach ($props as $p) {
            if (!empty($s[$p])) {
                $cssProp = str_replace('_', '-', $p);
                if ($p == 'bg_gradient')
                    $cssProp = 'background'; // Special case
                $css .= "$cssProp: " . $s[$p] . "; ";
            }
        }
        return $css;
    }
}
?>