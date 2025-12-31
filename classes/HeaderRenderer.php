<?php
class HeaderRenderer
{
    private $pdo;
    private $settings;
    private $globalSettings;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    private function loadSettings()
    {
        // 1. Fetch Header Builder Settings
        $stmt = $this->pdo->query("SELECT settings FROM header_settings WHERE id = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $default = [
            'general' => ['layout' => 'logo_left', 'type' => 'standard', 'container' => 'container'],
            'design' => ['typography' => ['menu_color' => '#333']]
        ];
        $this->settings = $row ? json_decode($row['settings'], true) : $default;

        // 2. Fetch Global Settings (Logo, Site Title)
        $stmtGlobal = $this->pdo->query("SELECT logo, site_title FROM settings WHERE id = 1");
        $this->globalSettings = $stmtGlobal->fetch(PDO::FETCH_ASSOC);
    }

    public function render()
    {
        $s = $this->settings;
        $g = $this->globalSettings;

        $layout = $s['general']['layout'] ?? 'logo_left';
        $stickyClass = ($s['general']['sticky'] ?? 0) ? 'sticky-top' : '';
        $typeClass = ($s['general']['type'] ?? 'standard') === 'transparent' ? 'header-transparent' : 'header-standard';

        // 1. Render Styles
        $this->renderDynamicStyles();

        echo "<header class='site-header $stickyClass $typeClass'>";

        // 2. Top Bar (If visible)
        if (!empty($s['rows']['top_bar']['visible'])) {
            $tb = $s['rows']['top_bar'];
            echo "<div class='top-bar py-2' style='background-color: {$tb['bg_color']}; color: {$tb['text_color']};'>";
            echo "<div class='container text-center text-md-end font-small'>" . htmlspecialchars($tb['text']) . "</div>";
            echo "</div>";
        }

        // 3. Main Header
        echo "<nav class='navbar navbar-expand-lg main-navigation'>";
        echo "<div class='" . ($s['general']['container'] ?? 'container') . "'>";

        // Toggle Button (Mobile)
        // Icon Logic
        $n = $s['navigation'] ?? [];
        $toggleIconClass = $n['mobile']['toggle_icon'] ?? 'bi-list';
        $mobLinkColor = $n['mobile']['link_color'] ?? '#333';
        // Map standard 'navbar-toggler-icon' behaviour if strictly 'bi-list', 
        // OR render the BI icon directly if it's something custom like dots/grid.

        $iconHtml = '<span class="navbar-toggler-icon"></span>'; // Default
        if ($toggleIconClass !== 'bi-list') {
            // Custom Icon
            $iconHtml = "<i class='$toggleIconClass' style='font-size: 1.5rem; color: $mobLinkColor;'></i>";
        }

        echo '<button class="navbar-toggler border-0 p-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                ' . $iconHtml . '
              </button>';

        // LOGO LOGIC (Dynamic)
        // Check user preference (Default to 'image' if not set)
        $brandingType = $s['general']['branding_type'] ?? 'image';

        $logoHtml = "";

        if ($brandingType === 'image' && !empty($g['logo']) && file_exists(__DIR__ . '/../uploads/' . $g['logo'])) {
            // Image Logo
            $logoHtml = "<a class='navbar-brand' href='home'>
                            <img src='uploads/{$g['logo']}' alt='Logo' style='max-height: 50px;'>
                          </a>";
        } else {
            // Text Logo (Site Title)
            $siteTitle = $g['site_title'] ?? 'SaoirseTours';
            // Use configured typography color or fallback
            $brandColor = $d['typography']['logo_color'] ?? '#000';
            $logoHtml = "<a class='navbar-brand fw-bold' href='home' style='color: $brandColor !important;'>$siteTitle</a>";
        }

        // MENU ITEMS
        // MENU ITEMS (Dynamic)
        $menuTree = [];
        try {
            // Check if table exists first/fetch directly
            $stmt = $this->pdo->query("SELECT * FROM menu_items WHERE menu_id = 1 AND is_active = 1 ORDER BY order_index ASC");
            if ($stmt) {
                $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Build Tree
                $itemsById = [];
                foreach ($allItems as $item) {
                    $item['children'] = [];
                    $itemsById[$item['id']] = $item;
                }
                foreach ($itemsById as $id => $item) {
                    if ($item['parent_id'] == 0) {
                        $menuTree[$id] = &$itemsById[$id];
                    } else {
                        if (isset($itemsById[$item['parent_id']])) {
                            $itemsById[$item['parent_id']]['children'][] = &$itemsById[$id];
                        }
                    }
                }
            }
        } catch (Exception $e) { /* ignore */
        }

        // Determine active class helper
        $currPage = basename($_SERVER['PHP_SELF'], '.php');

        // Start Buffering Menu HTML
        ob_start();
        echo '<ul class="navbar-nav gap-3">';

        if (empty($menuTree)) {
            // Fallback Defaults
            echo '<li class="nav-item"><a class="nav-link" href="home">Home</a></li>';
            echo '<li class="nav-item"><a class="nav-link" href="about">About Us</a></li>';
            echo '<li class="nav-item"><a class="nav-link" href="packages">Packages</a></li>';
            echo '<li class="nav-item"><a class="nav-link" href="contact">Book Now</a></li>';
        } else {
            foreach ($menuTree as $item) {
                $hasChildren = !empty($item['children']);
                $isActive = ($currPage == $item['url']) ? 'active' : '';
                // Resolve URL (handle external vs internal)
                $url = $item['url'];

                if ($hasChildren) {
                    echo '<li class="nav-item dropdown d-flex align-items-center">';
                    // If URL is valid, make text a link and add separate toggle
                    if ($url && $url !== '#') {
                        echo '<a class="nav-link ' . $isActive . ' me-1" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($item['title']) . '</a>';
                        echo '<a class="nav-link dropdown-toggle dropdown-toggle-split px-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">Toggle Dropdown</span></a>';
                    } else {
                        // Standard Toggle Pair
                        echo '<a class="nav-link dropdown-toggle ' . $isActive . '" href="#" role="button" data-bs-toggle="dropdown">' . htmlspecialchars($item['title']) . '</a>';
                    }
                    echo '<ul class="dropdown-menu">';
                    foreach ($item['children'] as $child) {
                        echo '<li><a class="dropdown-item" href="' . htmlspecialchars($child['url']) . '">' . htmlspecialchars($child['title']) . '</a></li>';
                    }
                    echo '</ul>';
                    echo '</li>';
                } else {
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link ' . $isActive . '" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($item['title']) . '</a>';
                    echo '</li>';
                }
            }
        }
        echo '</ul>';
        $menuInfo = ob_get_clean();

        // Append Book Btn if not present in menu? 
        // User might want it separate. The hardcoded one had it. 
        // Let's inject it via JS or separate logic? No, let's append it to the UL if it's the standard layout.
        // Actually, the previous code had it INSIDE the UL.
        // I will append it manually for now to ensure CTA exists, or rely on user adding it.
        // Better: Add it to the $menuInfo output before closing UL if the user wants strict control.
        // BUT, the prompt implies "Manager" controls links.
        // I'll leave the "Book Now" as a hardcoded append for safety, or user can add it as a Custom Link?
        // Let's just append it to the buffer if it's not "logo_center" mode maybe?
        // Simpler: Just append it inside the UL in the buffer above.
        // Re-opening buffer logic...

        // Inject CTA Button via string replacement to be safe/easy
        $menuInfo = str_replace('</ul>', '<li class="nav-item"><a class="nav-link btn-book px-4 text-white" href="contact">Book Now</a></li></ul>', $menuInfo);

        // LAYOUT LOGIC
        if ($layout === 'logo_center') {
            // Center Layout
            echo "<div class='d-flex w-100 justify-content-between align-items-center d-lg-none'>$logoHtml</div>"; // Mobile Logo
            echo "<div class='collapse navbar-collapse justify-content-center text-center' id='mainNav'>";
            echo "<div class='d-flex flex-column align-items-center'>";
            echo "<div class='mb-3 d-none d-lg-block'>$logoHtml</div>"; // Desktop Centered Logo
            echo $menuInfo;
            echo "</div>";
            echo "</div>";
        } else {
            // Left Layout (Standard)
            echo $logoHtml;
            echo "<div class='collapse navbar-collapse justify-content-end' id='mainNav'>";
            echo $menuInfo;
            echo "</div>";
        }

        echo "</div>"; // End Container
        echo "</nav>";
        echo "</header>";
    }

    private function renderDynamicStyles()
    {
        $s = $this->settings;
        $d = $s['design'];
        $n = $s['navigation'] ?? [];
        $r = $s['rows']['main_header'] ?? [];

        // --- 1. General Config ---
        $bgColor = $r['bg_color'] ?? '#ffffff';
        $height = $r['height'] ?? '80px';
        $bottomBorder = $d['borders']['bottom_width'] ?? '0px';
        $borderColor = $d['borders']['bottom_color'] ?? '#eee';
        $logoColor = $d['typography']['logo_color'] ?? '#000'; // Fallback text color

        // --- 2. Advanced Navigation Config ---
        // Typography
        $navFontFamily = !empty($n['typography']['font_family']) && $n['typography']['font_family'] !== 'inherit' ? $n['typography']['font_family'] : 'var(--font-primary)';
        $navWeight = $n['typography']['font_weight'] ?? '500';
        $navTransform = $n['typography']['text_transform'] ?? 'none';
        $navSize = ($n['typography']['font_size'] ?? '16') . 'px';
        $navSpacing = ($n['typography']['item_spacing'] ?? '15') . 'px';

        // Colors
        $linkColor = $n['colors']['link_color'] ?? ($d['typography']['menu_color'] ?? '#333');
        $linkHover = $n['colors']['link_hover_color'] ?? ($d['typography']['menu_hover'] ?? '#000');
        $linkActive = $n['colors']['link_active_color'] ?? $linkHover;

        // Dropdown
        $ddBg = $n['colors']['dropdown_bg'] ?? '#ffffff';
        $ddWidth = ($n['dropdown']['width'] ?? '220') . 'px';
        $ddDivider = !empty($n['dropdown']['dividers']) ? '1px solid rgba(0,0,0,0.1)' : 'none';

        // Hover Effect
        $hoverStyle = $n['hover_effect']['style'] ?? 'none';

        // Mobile
        $mobLinkColor = $n['mobile']['link_color'] ?? '#333';


        echo "<style>
            .main-navigation {
                background-color: $bgColor;
                min-height: $height;
                border-bottom: $bottomBorder solid $borderColor;
                transition: all 0.3s ease;
            }
            
            /* Logo */
            .navbar-brand {
                color: $logoColor !important;
                font-size: 1.5rem;
                display: flex;
                align-items: center;
            }
            .navbar-brand img { transition: transform 0.3s; }
            .navbar-brand:hover img { transform: scale(1.05); }
            
            /* --- NAVIGATION LINKS --- */
            .navbar-nav .nav-link {
                color: $linkColor !important;
                font-family: $navFontFamily;
                font-weight: $navWeight;
                text-transform: $navTransform;
                font-size: $navSize;
                padding-left: $navSpacing !important;
                padding-right: $navSpacing !important;
                position: relative;
                transition: color 0.3s ease;
            }

            .navbar-nav .nav-link:hover {
                color: $linkHover !important;
            }
            .navbar-nav .nav-link.active {
                color: $linkActive !important;
            }

            /* --- HOVER EFFECTS --- */
            ";

        // Generate Hover Style CSS
        if ($hoverStyle === 'underline') {
            echo "
            .navbar-nav .nav-link::after {
                content: ''; position: absolute; bottom: 5px; left: $navSpacing; right: $navSpacing;
                height: 2px; background-color: $linkHover;
                transform: scaleX(0); transition: transform 0.3s ease;
            }
            .navbar-nav .nav-link:hover::after,
            .navbar-nav .nav-link.active::after { transform: scaleX(1); }
            ";
        } elseif ($hoverStyle === 'overline') {
            echo "
            .navbar-nav .nav-link::before {
                content: ''; position: absolute; top: 5px; left: $navSpacing; right: $navSpacing;
                height: 2px; background-color: $linkHover;
                transform: scaleX(0); transition: transform 0.3s ease;
            }
            .navbar-nav .nav-link:hover::before,
            .navbar-nav .nav-link.active::before { transform: scaleX(1); }
            ";
        } elseif ($hoverStyle === 'framed') {
            echo "
            .navbar-nav .nav-link {
                border: 1px solid transparent;
                border-radius: 4px;
            }
            .navbar-nav .nav-link:hover {
                border-color: $linkHover;
                background-color: rgba(0,0,0,0.02);
            }
            ";
        }

        echo "
            /* --- DROPDOWNS --- */
            .dropdown-menu {
                background-color: $ddBg;
                min-width: $ddWidth;
                border: none;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                padding: 10px 0;
                border-radius: 0;
            }
            .dropdown-item {
                color: $linkColor;
                padding: 8px 20px;
                border-bottom: $ddDivider;
                font-size: 0.95em;
            }
            .dropdown-item:last-child { border-bottom: none; }
            .dropdown-item:hover {
                background-color: rgba(0,0,0,0.03);
                color: $linkHover;
                padding-left: 25px; /* Slight slide effect */
                transition: all 0.2s;
            }

            /* CTA Button Override (Keep it distinct) */
            .btn-book {
                background-color: var(--primary-color, #486856) !important;
                border-radius: 50px;
                color: #fff !important;
                padding: 8px 25px !important;
            }
            .btn-book:hover { opacity: 0.9; color: #fff !important; }

            /* --- MOBILE OVERRIDES --- */
            @media (max-width: 991px) {
                .navbar-collapse {
                    background-color: $ddBg;
                    padding: 20px;
                    border-top: 1px solid rgba(0,0,0,0.05);
                    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
                }
                .navbar-nav .nav-link {
                    padding: 10px 0 !important;
                    color: $mobLinkColor !important;
                }
            }

            /* Transparent Header Mode */
            .header-transparent .main-navigation {
                background-color: transparent !important;
                border-bottom: none;
                position: absolute; width: 100%; z-index: 1000;
            }
        </style>";
    }
}
?>