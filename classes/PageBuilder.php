<?php
class PageBuilder
{

    public static function renderSection($section, $pdo)
    {
        $stmt = $pdo->prepare("SELECT * FROM section_elements WHERE section_id = ? ORDER BY display_order ASC");
        $stmt->execute([$section['id']]);
        $elements = $stmt->fetchAll();

        // 1. Decode Advanced Settings
        $adv = json_decode($section['section_settings'] ?? '{}', true);

        // 2. Generate Container Styles (Strict Overrides)
        $style = "";

        // Dimensions & Spacing
        $style .= "padding-top: " . ($adv['spacing']['padding_top'] ?? $section['padding_top'] ?? '50px') . "; ";
        $style .= "padding-bottom: " . ($adv['spacing']['padding_bottom'] ?? $section['padding_bottom'] ?? '50px') . "; ";
        $style .= "padding-left: " . ($adv['spacing']['padding_left'] ?? '0px') . "; ";
        $style .= "padding-right: " . ($adv['spacing']['padding_right'] ?? '0px') . "; ";
        $style .= "margin-top: " . ($adv['spacing']['margin_top'] ?? '0px') . "; ";
        $style .= "margin-bottom: " . ($adv['spacing']['margin_bottom'] ?? '0px') . "; ";

        // Height Logic
        $minHeight = $adv['layout']['min_height'] ?? $section['min_height'] ?? 'auto';
        $style .= "min-height: " . $minHeight . "; ";
        if ($minHeight !== 'auto')
            $style .= "display: flex; flex-direction: column; justify-content: center; ";

        // Background Logic (Single Source of Truth)
        $bgType = $adv['background']['type'] ?? $section['bg_type'] ?? 'color';

        // FIX: Transparency White Issue
        // If background is explicit transparent, force it.
        // If no background color set, default to transparent, NOT white.
        $bgColor = $adv['background']['color'] ?? $section['bg_color'] ?? 'transparent';
        if (($adv['background']['transparent'] ?? 0) == 1) {
            $bgColor = 'transparent';
        }

        if ($bgType === 'color') {
            $style .= "background-color: $bgColor; ";
        } elseif ($bgType === 'gradient') {
            $grad = $adv['background']['gradient'] ?? $section['bg_gradient'] ?? '';
            $style .= "background: $grad; ";
        } elseif ($bgType === 'image') {
            $img = $adv['background']['image'] ?? $section['image'] ?? '';
            if ($img) {
                // If using Full Blur filter on image itself
                if (($adv['background']['blur_mode'] ?? 'none') === 'full') {
                    // Handled via pseudo-element typically, but for inline simplicity:
                    // We will use the overlay div for the image to support blurring it separate from content
                    $style .= "background-color: $bgColor; position: relative; overflow: hidden; ";
                } else {
                    $style .= "background: url('uploads/" . htmlspecialchars($img) . "') no-repeat center center / cover; ";
                }
            } else {
                $style .= "background-color: $bgColor; ";
            }
        }

        // Text Align override
        $textAlign = $adv['typography']['align'] ?? $section['text_align'] ?? 'left';
        $style .= "text-align: $textAlign; ";

        // Border
        if (!empty($adv['border']['width'])) {
            $style .= "border: " . $adv['border']['width'] . " " . ($adv['border']['style'] ?? 'solid') . " " . ($adv['border']['color'] ?? '#000') . "; ";
            $style .= "border-radius: " . ($adv['border']['radius'] ?? '0') . "; ";
        }

        // Render Section Wrapper
        echo '<section id="sec-' . $section['id'] . '" class="builder-section position-relative ' . htmlspecialchars($section['custom_css_class'] ?? '') . '" style="' . $style . '">';

        // --- BACKGROUND LAYERS ---

        // 1. Separate Background Image Div for "Full Blur" (if active)
        if ($bgType === 'image' && ($adv['background']['blur_mode'] ?? 'none') === 'full') {
            $blurPx = $adv['background']['blur_px'] ?? '0px';
            $img = $adv['background']['image'] ?? $section['image'] ?? '';
            echo '<div class="position-absolute top-0 start-0 w-100 h-100" style="background: url(\'uploads/' . $img . '\') center/cover; filter: blur(' . $blurPx . '); z-index: 0;"></div>';
        }

        // 2. Overlay Div (Color + Opacity + Backdrop Blur)
        $overlayOp = $adv['background']['overlay_opacity'] ?? 0;
        $overlayCol = $adv['background']['overlay_color'] ?? '#000000';
        $backdropBlur = ($adv['background']['blur_mode'] ?? 'none') === 'backdrop';

        if ($overlayOp > 0 || $backdropBlur) {
            $blurCss = $backdropBlur ? "backdrop-filter: blur(" . ($adv['background']['blur_px'] ?? '5px') . ");" : "";
            // Use RGB(a) conversion or just opacity style
            // Simple approach: Div with opacity
            echo '<div class="position-absolute top-0 start-0 w-100 h-100 section-overlay" style="background-color: ' . $overlayCol . '; opacity: ' . $overlayOp . '; ' . $blurCss . ' z-index: 1; pointer-events: none;"></div>';
        }

        // 3. Dividers (SVG)
        self::renderDivider($adv['dividers']['top'] ?? 'none', 'top', $adv['dividers']['color'] ?? '#ffffff');
        self::renderDivider($adv['dividers']['bottom'] ?? 'none', 'bottom', $adv['dividers']['color'] ?? '#ffffff');

        // --- CONTENT ---
        $containerClass = ($adv['layout']['width'] ?? 'boxed') === 'full' ? 'container-fluid' : 'container';

        echo "<div class='$containerClass position-relative' style='z-index: 2;'>";
        echo '<div class="row align-items-center justify-content-' . ($textAlign === 'center' ? 'center' : 'start') . '">'; // Flex alignment
        echo '<div class="col-12">';

        if (empty($elements) && !empty($section['content'])) {
            echo $section['content'];
        }

        // SMART GRID RENDERING SYSTEM
        $count = count($elements);
        for ($i = 0; $i < $count; $i++) {
            $el = $elements[$i];
            $isCard = ($el['type'] === 'card');

            if ($isCard) {
                // Check if we need to OPEN a new grid row
                // We open if this is the first card, OR the previous one wasn't a card
                $prevWasCard = ($i > 0) && ($elements[$i - 1]['type'] === 'card');

                if (!$prevWasCard) {
                    echo '<div class="row justify-content-center">'; // Start Card Grid
                }

                // Render the card wrapper
                echo '<div class="col-md-6 col-lg-4 mb-4">';
                self::renderElement($el);
                echo '</div>';

                // Check if we need to CLOSE the grid row
                // We close if this is the last element, OR the next one isn't a card
                $nextIsCard = ($i < $count - 1) && ($elements[$i + 1]['type'] === 'card');

                if (!$nextIsCard) {
                    echo '</div>'; // End Card Grid
                }
            } else {
                // Normal Element Rendering
                self::renderElement($el);
            }
        }

        echo '</div></div></div>'; // End Col, Row, Container
        echo '</section>';
    }

    private static function renderDivider($type, $pos, $color)
    {
        if ($type === 'none')
            return;

        $svg = "";
        // Simple SVG shapes
        if ($type === 'wave') {
            $svg = '<svg viewBox="0 0 1440 320" preserveAspectRatio="none" style="width:100%;height:100%;"><path fill="' . $color . '" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>';
        } elseif ($type === 'slant') {
            $svg = '<svg viewBox="0 0 1440 320" preserveAspectRatio="none" style="width:100%;height:100%;"><path fill="' . $color . '" fill-opacity="1" d="M0,320L1440,0L1440,320L0,320Z"></path></svg>';
        }

        // Position styles
        $yPos = $pos === 'top' ? 'top: 0;' : 'bottom: 0;';
        $transform = $pos === 'top' ? 'transform: rotate(180deg);' : '';

        echo '<div class="position-absolute w-100 overflow-hidden" style="' . $yPos . ' left: 0; height: 100px; z-index: 2; line-height: 0; ' . $transform . ' pointer-events: none;">';
        echo $svg;
        echo '</div>';
    }

    // (renderElement method remains same, keeping previous improvements)
    private static function renderElement($el)
    {
        $s = json_decode($el['settings'], true) ?? [];
        $style = self::generateStyle($s);
        $content = $el['content'];
        $wrapperStyle = "text-align: " . ($s['text_align'] ?? 'inherit') . "; margin-bottom: " . ($s['margin_bottom'] ?? '20px') . "; margin-top: " . ($s['margin_top'] ?? '0') . ";";
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
                $src = (strpos($content, 'http') === 0) ? $content : "uploads/" . htmlspecialchars($content);
                echo "<img src='$src' style='$style max-width:$width; height:auto;' alt=''>";
                break;
            case 'button':
                echo "<a href='" . htmlspecialchars($s['url'] ?? '#') . "' class='btn " . ($s['style'] ?? 'btn-primary') . "' style='$style'>" . htmlspecialchars($content) . "</a>";
                break;
            case 'icon':
                echo "<i class='bi " . htmlspecialchars($content) . "' style='font-size:" . ($s['font_size'] ?? '2rem') . "; color:" . ($s['color'] ?? 'var(--primary)') . "; $style'></i>";
                break;
            case 'video':
                $v = $content;
                if (strpos($v, 'youtu') !== false)
                    $v = str_replace("watch?v=", "embed/", $v);
                echo "<div class='ratio ratio-16x9' style='max-width:" . ($s['width'] ?? '100%') . ";margin:0 auto;$style'><iframe src='$v' allowfullscreen></iframe></div>";
                break;
            case 'map':
                // Simple responsive wrapper for Map
                echo "<div class='map-wrapper' style='width:100%; height:" . ($s['height'] ?? '400px') . "; $style overflow:hidden;'>";
                // Force width/height 100% on iframe if present
                $m = $content;
                if (strpos($m, '<iframe') !== false) {
                    $m = preg_replace('/width="[^"]+"/', 'width="100%"', $m);
                    $m = preg_replace('/height="[^"]+"/', 'height="100%"', $m);
                }
                echo $m;
                echo "</div>";
                break;
            case 'card':
                $cardBg = $s['bg_color'] ?? '#ffffff';
                $cardCol = $s['color'] ?? 'inherit';
                $shadowClass = ($s['shadow'] ?? '0') == '1' ? 'shadow' : 'shadow-sm';

                // Card Wrapper with Hover Effect
                echo "<div class='card h-100 border-0 $shadowClass' style='background:$cardBg; color:$cardCol; transition: transform 0.3s ease, box-shadow 0.3s ease; overflow:hidden;' onmouseover='this.style.transform=\"translateY(-5px)\"; this.style.boxShadow=\"0 .5rem 1rem rgba(0,0,0,.15)\"' onmouseout='this.style.transform=\"translateY(0)\"; this.style.boxShadow=\"\"'>";

                // Image
                if (!empty($s['card_image'])) {
                    echo "<div class='position-relative' style='height: 220px; overflow:hidden;'>";
                    echo "<img src='uploads/" . htmlspecialchars($s['card_image']) . "' class='card-img-top w-100 h-100' style='object-fit: cover; transition: transform 0.5s ease;' onmouseover='this.style.transform=\"scale(1.05)\"' onmouseout='this.style.transform=\"scale(1)\"'>";
                    echo "</div>";
                }

                echo "<div class='card-body' style='padding:" . ($s['padding'] ?? '2rem') . ";'>";

                // Title
                if (!empty($s['card_title'])) {
                    echo "<h5 class='card-title fw-bold mb-3' style='color: " . ($s['title_color'] ?? 'inherit') . "'>" . htmlspecialchars($s['card_title']) . "</h5>";
                }

                // Content
                echo "<div class='card-text opacity-75'>$content</div>";

                echo "</div></div>";
                break;
            case 'spacer':
                echo "<div style='height:" . ($s['height'] ?? '50px') . ";'></div>";
                break;
            case 'divider':
                echo "<hr style='border-color:" . ($s['color'] ?? '#ccc') . ";opacity:1;margin:0;$style'>";
                break;
            case 'list':
                $t = ($s['list_type'] ?? 'ul') == 'ol' ? 'ol' : 'ul';
                echo "<$t style='$style'>";
                foreach (explode("\n", $content) as $i)
                    if (trim($i))
                        echo "<li>" . htmlspecialchars($i) . "</li>";
                echo "</$t>";
                break;
        }
        echo "</div>";
    }

    private static function generateStyle($s)
    {
        $css = "";
        $props = ['padding', 'padding_top', 'padding_bottom', 'padding_left', 'padding_right', 'margin', 'margin_top', 'margin_bottom', 'margin_left', 'margin_right', 'color', 'background_color', 'font_size', 'font_weight', 'line_height', 'letter_spacing', 'border_radius', 'border', 'box_shadow', 'min_height', 'bg_gradient', 'opacity'];
        foreach ($props as $p) {
            if (!empty($s[$p])) {
                $cssProp = str_replace('_', '-', $p);
                if ($p == 'bg_gradient')
                    $cssProp = 'background';
                $css .= "$cssProp: " . $s[$p] . "; ";
            }
        }
        return $css;
    }
}
?>