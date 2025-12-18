<?php
class ThemeHelper
{
    public static function getTheme($pdo)
    {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $stmt->fetch();

        $theme = json_decode($settings['theme_json'] ?? '{}', true);

        // Defaults
        return [
            'colors' => array_merge([
                'primary' => $settings['primary_color'] ?? '#0d6efd',
                'secondary' => $settings['secondary_color'] ?? '#6c757d',
                'bg_body' => $settings['bg_color'] ?? '#ffffff',
                'text_main' => '#212529',
                'surface' => '#ffffff'
            ], $theme['colors'] ?? []),

            'typography' => array_merge([
                'font_primary' => $settings['font_family'] ?? "'Inter', sans-serif",
                'font_secondary' => "'Inter', sans-serif",
                'base_size' => '16px',
                'line_height' => '1.5'
            ], $theme['typography'] ?? []),

            'nav' => array_merge([
                'bg' => '#ffffff',
                'text_color' => '#212529',
                'height' => '70px',
                'padding' => '10px 0',
                'sticky' => '1'
            ], $theme['nav'] ?? []),

            'footer' => array_merge([
                'bg' => '#212529',
                'text_color' => '#ffffff',
                'padding' => '50px 0'
            ], $theme['footer'] ?? [])
        ];
    }

    public static function renderCssVariables($theme)
    {
        $css = ":root {\n";

        // Colors
        foreach ($theme['colors'] as $key => $val) {
            $css .= "    --color-$key: $val;\n";
        }

        // Typography
        $css .= "    --font-primary: " . $theme['typography']['font_primary'] . ";\n";
        $css .= "    --font-secondary: " . $theme['typography']['font_secondary'] . ";\n";
        $css .= "    --font-base-size: " . $theme['typography']['base_size'] . ";\n";
        $css .= "    --line-height: " . $theme['typography']['line_height'] . ";\n";

        // Nav
        $css .= "    --nav-bg: " . $theme['nav']['bg'] . ";\n";
        $css .= "    --nav-text: " . $theme['nav']['text_color'] . ";\n";
        $css .= "    --nav-height: " . $theme['nav']['height'] . ";\n";
        $css .= "    --nav-padding: " . $theme['nav']['padding'] . ";\n";

        // Footer
        $css .= "    --footer-bg: " . $theme['footer']['bg'] . ";\n";
        $css .= "    --footer-text: " . $theme['footer']['text_color'] . ";\n";
        $css .= "    --footer-padding: " . $theme['footer']['padding'] . ";\n";

        $css .= "}\n";

        // Global Styles
        $css .= "body { font-family: var(--font-primary); font-size: var(--font-base-size); line-height: var(--line-height); background-color: var(--color-bg_body); color: var(--color-text_main); }\n";
        $css .= "h1, h2, h3, h4, h5, h6 { font-family: var(--font-secondary); font-weight: 700; color: var(--color-primary); }\n";
        $css .= "a { color: var(--color-primary); text-decoration: none; transition: 0.3s; }\n";
        $css .= "a:hover { color: var(--color-secondary); }\n";

        // Button Styles
        $css .= ".btn-primary { background-color: var(--color-primary); border-color: var(--color-primary); }\n";
        $css .= ".btn-primary:hover { background-color: var(--color-secondary); border-color: var(--color-secondary); }\n";
        $css .= ".btn-secondary { background-color: var(--color-secondary); border-color: var(--color-secondary); }\n";

        return $css;
    }
}
?>