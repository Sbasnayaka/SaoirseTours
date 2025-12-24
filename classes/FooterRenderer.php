<?php
class FooterRenderer
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
        // Fetch Footer Settings
        try {
            $stmt = $this->pdo->query("SELECT settings FROM footer_settings WHERE id = 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->settings = $row ? json_decode($row['settings'], true) : [];
        } catch (PDOException $e) {
            // Table might not exist yet
            $this->settings = [];
        }
    }

    public function render()
    {
        // Defaults
        $s = $this->settings;
        $bgColor = $s['design']['bg_color'] ?? '#1e1e2d';
        $textColor = $s['design']['text_color'] ?? '#ffffff';
        $headingColor = $s['design']['heading_color'] ?? '#ffffff';

        // CSS Variables for scoped styling
        echo "<style>
            .site-footer {
                background-color: $bgColor;
                color: $textColor;
                font-size: 0.9rem;
            }
            .site-footer h5 {
                color: $headingColor;
                font-weight: 700;
                margin-bottom: 1.5rem;
                font-size: 1.1rem;
            }
            .site-footer a {
                color: $textColor;
                text-decoration: none;
                transition: opacity 0.3s;
            }
            .site-footer a:hover {
                opacity: 0.8;
            }
            .site-footer ul li {
                margin-bottom: 10px;
            }
            .footer-bottom {
                border-top: 1px solid rgba(255,255,255,0.1);
                padding-top: 20px;
                margin-top: 40px;
                font-size: 0.85rem;
                opacity: 0.8;
            }
            .social-links a {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                background: rgba(255,255,255,0.1);
                border-radius: 50%;
                margin-right: 10px;
                transition: all 0.3s;
            }
            .social-links a:hover {
                background: var(--primary-color, #486856);
                transform: translateY(-3px);
            }
        </style>";

        echo "<footer class='site-footer py-5 mt-5'>";
        echo "<div class='container'>";
        echo "<div class='row g-4'>";

        // COL 1: TripAdvisor Widget
        echo "<div class='col-lg-3 col-md-6'>";
        // Placeholder or Widget Code
        $taCode = $s['widgets']['tripadvisor_html'] ?? '';
        if (!empty($taCode)) {
            echo $taCode;
        } else {
            // Default Placeholder
            echo "<div class='bg-white p-3 rounded text-center text-dark mb-3'>";
            echo "<i class='bi bi-tripadvisor fs-1 text-success'></i>";
            echo "<h6 class='fw-bold mt-2'>Review Us on TripAdvisor</h6>";
            echo "<div class='small text-muted'>5.0 Stars (120 Reviews)</div>";
            echo "</div>";
        }

        // Socials (under widget as per layout idea, or separate)
        echo "<h5 class='mt-4'>Follow Us On:</h5>";
        echo "<div class='social-links'>";
        $socials = $s['widgets']['socials'] ?? [];
        if (!empty($socials['facebook']))
            echo "<a href='{$socials['facebook']}' target='_blank'><i class='bi bi-facebook'></i></a>";
        if (!empty($socials['instagram']))
            echo "<a href='{$socials['instagram']}' target='_blank'><i class='bi bi-instagram'></i></a>";
        if (!empty($socials['whatsapp']))
            echo "<a href='{$socials['whatsapp']}' target='_blank'><i class='bi bi-whatsapp'></i></a>";
        if (!empty($socials['tripadvisor_url']))
            echo "<a href='{$socials['tripadvisor_url']}' target='_blank'><i class='bi bi-tripadvisor'></i></a>";
        echo "</div>";
        echo "</div>";

        // COL 2: Tour Packages (Dynamic)
        echo "<div class='col-lg-3 col-md-6'>";
        echo "<h5>Tour Packages</h5>";
        echo "<ul class='list-unstyled'>";

        // Fetch top 5 packages
        try {
            $stmt = $this->pdo->query("SELECT id, title FROM packages WHERE status = 'published' LIMIT 5");
            while ($pkg = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<li><a href='package-detail.php?id={$pkg['id']}'>" . htmlspecialchars($pkg['title']) . "</a></li>";
            }
        } catch (PDOException $e) {
            echo "<li>No packages found</li>";
        }
        echo "</ul>";
        echo "</div>";

        // COL 3: Quick Links
        echo "<div class='col-lg-3 col-md-6'>";
        echo "<h5>Quick Link</h5>";
        echo "<ul class='list-unstyled'>";
        $links = $s['widgets']['quick_links'] ?? [
            ['text' => 'Home', 'url' => 'home'],
            ['text' => 'About Us', 'url' => 'about'],
            ['text' => 'Packages', 'url' => 'packages'],
            ['text' => 'Contact Us', 'url' => 'contact']
        ];
        foreach ($links as $link) {
            $url = htmlspecialchars($link['url']);
            $text = htmlspecialchars($link['text']);
            echo "<li><a href='$url'>$text</a></li>";
        }
        echo "</ul>";
        echo "</div>";

        // COL 4: Info & Trust Badges
        echo "<div class='col-lg-3 col-md-6'>";

        // Fetch global address/phone settings fallback if needed, or use static description
        // User asked for "Icons" area.

        // Description
        echo "<p class='mb-4'>Exceptional vacation experiences in the tropical island of Sri Lanka.</p>";

        // Badges / Icons (HTML Area)
        $badges = $s['widgets']['trust_badges_html'] ?? '';
        if (!empty($badges)) {
            echo "<div class='trust-badges'>$badges</div>";
        } else {
            // Fallback Badges placeholder
            echo "<div class='d-flex flex-wrap gap-2'>";
            echo "<span class='badge bg-warning text-dark p-2'><i class='bi bi-shield-check'></i> Safe Travels</span>";
            echo "<span class='badge bg-success p-2'><i class='bi bi-tree'></i> Sustainable</span>";
            echo "</div>";
        }

        echo "</div>"; // End Col 4

        echo "</div>"; // End Row

        // BOTTOM BAR
        echo "<div class='footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center'>";
        $copy = $s['widgets']['copyright_text'] ?? "Copyright " . date('Y') . " Saoirse Tours | All Rights Reserved";

        // Contact Info Bar (as distinct from copyright)
        echo "<div class='mb-2 mb-md-0 fw-bold'>";
        echo "Contact Us: <span class='ms-2 me-4'>+94 77 123 4567</span> info@saoirsetours.com";
        echo "</div>";

        echo "<div class='text-md-end'>";
        echo htmlspecialchars($copy);
        echo "</div>";

        echo "</div>"; // End Bottom Bar

        echo "</div>"; // End Container
        echo "</footer>";
    }
}
?>