<?php
class HeaderRenderer
{
    private $pdo;
    private $settings;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    private function loadSettings()
    {
        $stmt = $this->pdo->query("SELECT settings FROM header_settings WHERE id = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // Default Fallback
        $default = [
            'general' => ['layout' => 'logo_left', 'type' => 'standard', 'container' => 'container'],
            'design' => ['typography' => ['menu_color' => '#333']]
        ];

        $this->settings = $row ? json_decode($row['settings'], true) : $default;
    }

    public function render()
    {
        $s = $this->settings;
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
        echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
              </button>';

        // LOGO
        $logo = "<a class='navbar-brand fw-bold' href='home'>SaoirseTours</a>";

        // MENU ITEMS
        $menuInfo = '<ul class="navbar-nav gap-3">
                        <li class="nav-item"><a class="nav-link" href="home">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="about">About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="packages">Packages</a></li>
                        <li class="nav-item"><a class="nav-link" href="services">Services</a></li>
                        <li class="nav-item"><a class="nav-link" href="gallery">Gallery</a></li>
                        <li class="nav-item"><a class="nav-link btn-book px-4 text-white" href="contact">Book Now</a></li>
                    </ul>';

        // LAYOUT LOGIC
        if ($layout === 'logo_center') {
            // Center Layout
            echo "<div class='d-flex w-100 justify-content-between align-items-center d-lg-none'>$logo</div>"; // Mobile Logo
            echo "<div class='collapse navbar-collapse justify-content-center text-center' id='mainNav'>";
            echo "<div class='d-flex flex-column align-items-center'>";
            echo "<div class='mb-3 d-none d-lg-block'>$logo</div>"; // Desktop Centered Logo
            echo $menuInfo;
            echo "</div>";
            echo "</div>";
        } else {
            // Left Layout (Standard)
            echo $logo;
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
        $r = $s['rows']['main_header'] ?? [];

        $bgColor = $r['bg_color'] ?? '#ffffff';
        $menuColor = $d['typography']['menu_color'] ?? '#333';
        $menuHover = $d['typography']['menu_hover'] ?? '#000';
        $logoColor = $d['typography']['logo_color'] ?? '#000';
        $height = $r['height'] ?? '80px';
        $bottomBorder = $d['borders']['bottom_width'] ?? '0px';
        $borderColor = $d['borders']['bottom_color'] ?? '#eee';

        echo "<style>
            .main-navigation {
                background-color: $bgColor;
                min-height: $height;
                border-bottom: $bottomBorder solid $borderColor;
                transition: all 0.3s ease;
            }
            .navbar-brand {
                color: $logoColor !important;
                font-size: 1.5rem;
            }
            .nav-link {
                color: $menuColor !important;
                font-weight: 500;
                transition: color 0.2s;
            }
            .nav-link:hover, .nav-link.active {
                color: $menuHover !important;
            }
            .btn-book {
                background-color: var(--primary-color, #486856) !important;
                border-radius: 50px;
                color: #fff !important;
            }
            .btn-book:hover {
                opacity: 0.9;
            }
            /* Transparent Override */
            .header-transparent .main-navigation {
                background-color: transparent !important;
                border-bottom: none;
                position: absolute;
                width: 100%;
                z-index: 1000;
            }
        </style>";
    }
}
?>