<?php
// Resolve base URL (works when app is in a subfolder)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/models/Settings.php';
require_once __DIR__ . '/../../app/models/SocialLink.php';
require_once __DIR__ . '/../../app/models/Announcement.php';
require_once __DIR__ . '/../../app/models/Feedback.php';

// Optional PetCare model (if meron ka)
$petCareModelPath = __DIR__ . '/../../app/models/PetCareTip.php';
if (is_file($petCareModelPath)) require_once $petCareModelPath;

/* ---------- Helpers ---------- */
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function to_url(string $path, string $BASE): string {
    if ($path === '') return $BASE . 'assets/images/veterinarian_2.jpg';
    if (preg_match('~^(https?://|/)~i', $path)) return $path;
    return $BASE . ltrim($path, '/');
}
function contact_address(array $s): string {
    $parts = array_filter([
        $s['contact_houseno'] ?? '',
        $s['contact_street'] ?? '',
        $s['contact_barangay'] ?? '',
        $s['contact_municipality'] ?? '',
        $s['contact_province'] ?? '',
        isset($s['contact_zipcode']) && $s['contact_zipcode'] !== '' ? (string)$s['contact_zipcode'] : ''
    ], fn($v)=>trim((string)$v)!=='');
    return $parts ? implode(', ', $parts) : 'Marawi City, Philippines';
}
function excerpt($text, $len=160){
    $s = trim((string)$text);
    return (mb_strlen($s) <= $len) ? $s : rtrim(mb_substr($s,0,$len)," \t\n\r\0\x0B.").'…';
}
/** pretty date: Friday - August 22, 2025 | 8:19 AM */
function pretty_date(?string $dt): string {
    if (!$dt) return '';
    try {
        $d = new DateTime($dt, new DateTimeZone('Asia/Manila'));
        return $d->format('l - F j, Y | g:i A');
    } catch(Throwable $e){ return ''; }
}
/** avatar for a feedback: prefer by user_id, else by email, else default */
function avatar_for_feedback(array $fb, string $BASE): string {
    $pdo = db();
    try {
        if (!empty($fb['user_id'])) {
            $st = $pdo->prepare("SELECT COALESCE(p.avatar_path,p.photo_path,p.image_path,p.profile_image) 
                                   FROM user_profiles p WHERE p.user_id=? LIMIT 1");
            $st->execute([ (int)$fb['user_id'] ]);
            $a = $st->fetchColumn();
            if ($a) return to_url($a, $BASE);
        }
        if (!empty($fb['email'])) {
            $q = "SELECT COALESCE(p.avatar_path,p.photo_path,p.image_path,p.profile_image)
                    FROM user_profiles p 
                    JOIN users u ON u.id=p.user_id 
                   WHERE u.email=? LIMIT 1";
            $st = $pdo->prepare($q); $st->execute([ (string)$fb['email'] ]);
            $a = $st->fetchColumn();
            if ($a) return to_url($a, $BASE);
        }
    } catch(Throwable $e){}
    return $BASE.'assets/images/person1.jpg';
}

/* ---------- Branding bits ---------- */
$settings     = Settings::getAll();
$social_links = SocialLink::getAll();
$clinic_name   = $settings['clinic_name']   ?? 'PawPals';
$hero_title    = $settings['hero_title']    ?? 'We take care of your pets with experts 🐱';
$hero_subtitle = $settings['hero_subtitle'] ?? 'The best place for your best friend. Providing top-tier veterinary services and compassionate care in Marawi City.';
$hero_img_path = to_url($settings['hero_image_path'] ?? 'assets/images/veterinarian_2.jpg', $BASE);
$footer_tagline= $settings['footer_tagline'] ?? ($settings['clinic_tagline'] ?? 'Dedicated to providing top-tier veterinary services and compassionate care for your beloved pets in Marawi City.');
$contact_phone = $settings['contact_phone']  ?? '+63 912 345 6789';
$contact_email = $settings['contact_email']  ?? 'info@pawpals.com';
$contact_addr  = contact_address($settings);

/* ---------- Announcements (landing/both + published + not expired) ---------- */
$allAnns = Announcement::all();
$now = new DateTime('now');
$landingAnns = array_values(array_filter($allAnns, function($r) use ($now){
    $loc = strtolower((string)($r['location'] ?? ''));
    $published = (int)($r['is_published'] ?? 0) === 1;
    if (!in_array($loc, ['landing','both'], true)) return false;
    if (!$published) return false;
    $pubOK = true;
    if (!empty($r['published_at'])) { try { $pubOK = (new DateTime($r['published_at'])) <= $now; } catch(Throwable $e){} }
    $expOK = true;
    if (!empty($r['expires_at']))   { try { $expOK = (new DateTime($r['expires_at']))   >= $now; } catch(Throwable $e){} }
    return $pubOK && $expOK;
}));
usort($landingAnns, function($a,$b){
    $pa = !empty($a['published_at']) ? strtotime($a['published_at']) : -INF;
    $pb = !empty($b['published_at']) ? strtotime($b['published_at']) : -INF;
    if ($pa === $pb) return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
    return $pb <=> $pa;
});

/* ---------- Pet Care Tips (PUBLISHED + NOT EXPIRED) ---------- */
$DB = db();
function fetch_petcare_tips_direct(PDO $db): array {
    $sql = "SELECT id, title, body, category, image_path, external_url, file_path,
                   is_published, published_at, expires_at
              FROM pet_care_tips";
    $stmt = $db->query($sql);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}
if (class_exists('PetCareTip') && method_exists('PetCareTip', 'all')) {
    $tipsAll = call_user_func(['PetCareTip','all']);
} else {
    $tipsAll = fetch_petcare_tips_direct($DB);
}
$landingTips = array_values(array_filter($tipsAll, function($r) use ($now){
    $published = (int)($r['is_published'] ?? 0) === 1;
    if (!$published) return false;
    $pubOK = true;
    if (!empty($r['published_at'])) { try { $pubOK = (new DateTime($r['published_at'])) <= $now; } catch(Throwable $e){} }
    $expOK = true;
    if (!empty($r['expires_at']))   { try { $expOK = (new DateTime($r['expires_at']))   >= $now; } catch(Throwable $e){} }
    return $pubOK && $expOK;
}));
usort($landingTips, function($a,$b){
    $pa = !empty($a['published_at']) ? strtotime($a['published_at']) : -INF;
    $pb = !empty($b['published_at']) ? strtotime($b['published_at']) : -INF;
    if ($pa === $pb) return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
    return $pb <=> $pa;
});
$catSet = [];
foreach ($landingTips as $t) {
    $c = strtolower(trim((string)($t['category'] ?? '')));
    if ($c !== '') $catSet[$c] = true;
}
$cats = array_keys($catSet);
sort($cats);

/* ---------- Testimonials: approved only; sort rating DESC then date DESC ---------- */
$testimonials = Feedback::approvedForPublic(50);
usort($testimonials, function($a,$b){
    $r = (float)$b['rating'] <=> (float)$a['rating'];
    if ($r !== 0) return $r;
    $da = strtotime($a['approved_at'] ?: ($a['created_at'] ?? '1970-01-01'));
    $db = strtotime($b['approved_at'] ?: ($b['created_at'] ?? '1970-01-01'));
    return $db <=> $da;
});
$testimonials = array_slice($testimonials, 0, 12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($clinic_name) ?> - We take care of your pets with experts</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.core.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

    <link rel="stylesheet" href="<?= $BASE ?>assets/css/landing-page.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/Flip.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/glide.min.js" defer></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js" defer></script>

    <script>window.APP_BASE = <?= json_encode($BASE, JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="<?= $BASE ?>assets/js/landing-page.js" defer></script>

    <!-- fractional stars + rating input styles -->
    <style>
      /* display-only stars (testimonials) */
      .stars { position:relative; display:inline-block; font-size:14px; line-height:1; }
      .stars-bg, .stars-fill { letter-spacing:2px; }
      .stars-bg  { color:#cbd5e1; }
      .stars-fill{ color:#f59e0b; position:absolute; inset:0 auto 0 0; white-space:nowrap; overflow:hidden; width:0; }

      .testimonial-card .small { font-size:.8rem; color:#6b7280; }

      /* rating input (form) */
      .rating-input{ position:relative; display:inline-block; font-size:22px; cursor:pointer; user-select:none; line-height:1; }
      .rating-input .bg{ color:#e5e7eb; letter-spacing:2px; }
      .rating-input .fill{ color:#f59e0b; position:absolute; left:0; top:0; width:0; overflow:hidden; pointer-events:none; letter-spacing:2px; }
      .rating-reading{ margin-left:.35rem; color:#6b7280; font-size:.85rem; }
      @media (max-width: 767px){
        #tipsGrid{display:flex; overflow-x:auto; gap:1rem; scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch; padding-bottom:1rem;}
        #tipsGrid .tip-card{flex:0 0 85vw; scroll-snap-align:start;}
      }
    </style>
</head>
<body>

    <nav class="navbar" role="navigation" aria-label="Main Navigation">
        <a href="<?= $BASE ?>" class="logo"><?= e($clinic_name) ?></a>
        <ul class="navbar-nav" aria-hidden="true">
            <li class="nav-item"><a href="#home" class="nav-link">Home</a></li>
            <li class="nav-item"><a href="#announcements" class="nav-link">Announcements</a></li>
            <li class="nav-item"><a href="#news" class="nav-link">Pet Care</a></li>
            <li class="nav-item"><a href="#testimonials" class="nav-link">Testimonials</a></li>
            <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
            <li class="nav-item navbar-auth-mobile"><button class="btn" onclick="location.href='<?= $BASE ?>auth/login'">Login / Sign Up</button></li>
        </ul>
        <button class="btn navbar-auth-desktop" onclick="location.href='<?= $BASE ?>auth/login'">Login / Sign Up</button>
        <div class="hamburger" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu" role="button"><i class="fas fa-bars"></i></div>
    </nav>

    <header id="home" class="hero-section">
        <div class="container hero-container">
            <div class="hero-content" data-aos="fade-right">
                <h1><?= e($hero_title) ?></h1>
                <p><?= e($hero_subtitle) ?></p>
                <div class="hero-actions"><a href="<?= $BASE ?>auth/signup" class="btn">Get Started</a></div>
            </div>
            <div class="hero-image-container" data-aos="fade-left">
                <div class="hero-image-circle">
                    <img src="<?= e($hero_img_path) ?>" alt="Veterinarian taking care of a pet" loading="eager" decoding="async">
                </div>
                <div class="floating-badges">
                    <span class="badge badge-vaccination">Vaccination</span>
                    <span class="badge badge-health">Health Check</span>
                    <span class="badge badge-care">Pet Care</span>
                </div>
            </div>
        </div>
    </header>

    <main id="app-content">
        <!-- Announcements -->
        <section id="announcements" class="content-section" data-aos="fade-up">
            <div class="container">
                <h2>Announcements</h2>
                <?php if (count($landingAnns) === 0): ?>
                    <p class="muted">No announcements at the moment.</p>
                <?php else: ?>
                <div id="announcements-slider" class="splide" aria-label="Announcements Slider">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <?php foreach ($landingAnns as $a):
                                $title  = $a['title'] ?? '';
                                $body   = $a['body']  ?? '';
                                $img    = to_url($a['image_path'] ?? '', $BASE);
                                $url    = trim((string)($a['external_url'] ?? ''));
                                $desc   = excerpt($body, 160);
                                $hasImg = !empty($a['image_path']);
                            ?>
                            <li class="splide__slide"
                                data-title="<?= e($title) ?>"
                                data-description="<?= e($desc) ?>"
                                data-image="<?= e($hasImg ? $img : '') ?>"
                                data-type="<?= $url ? 'external' : 'none' ?>"
                                data-url="<?= e($url) ?>">
                                <div class="slide-content">
                                    <h3 class="slide-title"><?= e($title) ?></h3>
                                    <?php if ($hasImg): ?>
                                      <div class="slide-image-container"><img src="<?= e($img) ?>" alt="<?= e($title) ?>"></div>
                                    <?php endif; ?>
                                    <?php if ($desc): ?><p class="slide-description"><?= e($desc) ?></p><?php endif; ?>
                                    <button class="btn view-details">View Details</button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Pet Care Tips -->
        <section id="news" class="content-section" style="background-color:#F8F9FA;" data-aos="fade-left">
            <div class="container">
                <h2>Pet Care Tips</h2>

                <div class="filter-controls">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <?php foreach ($cats as $c): ?>
                      <button class="filter-btn" data-filter="<?= e($c) ?>"><?= e(ucwords(str_replace(['-','_'],' ', $c))) ?></button>
                    <?php endforeach; ?>
                </div>

                <?php if (count($landingTips) === 0): ?>
                  <p class="muted">No tips yet. Please check back soon.</p>
                <?php else: ?>
                <div class="tips-grid" id="tipsGrid">
                  <?php foreach ($landingTips as $t):
                    $title     = $t['title'] ?? '';
                    $bodyRaw   = (string)($t['body'] ?? '');
                    $bodyHtml  = nl2br(e($bodyRaw));
                    $excerptTx = excerpt($bodyRaw, 140);
                    $category  = strtolower(trim((string)($t['category'] ?? 'health')));
                    $imgPath   = trim((string)($t['image_path'] ?? ''));
                    $img       = $imgPath ? to_url($imgPath, $BASE) : $BASE.'assets/images/veterinarian_2.jpg';

                    $external  = trim((string)($t['external_url'] ?? ''));
                    $file      = trim((string)($t['file_path'] ?? ''));
                    $linkType  = $external ? 'external' : ($file ? 'file' : 'internal');
                    $linkUrl   = $external ?: ($file ? to_url($file, $BASE) : '');
                  ?>
                  <div class="card tip-card"
                       data-category="<?= e($category) ?>"
                       data-title="<?= e($title) ?>"
                       data-image="<?= e($img) ?>"
                       data-type="<?= e($linkType) ?>"
                       data-url="<?= e($linkUrl) ?>">
                      <img src="<?= e($img) ?>" alt="">
                      <div class="card-content">
                        <h3><?= e($title) ?></h3>
                        <?php if ($excerptTx): ?><p><?= e($excerptTx) ?></p><?php endif; ?>
                        <a href="javascript:void(0)" class="btn-read-more">Read More</a>
                      </div>
                      <div class="tip-article" hidden><?= $bodyHtml ?></div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

<!-- Testimonials -->
<section id="testimonials" class="content-section testimonials-section" data-aos="fade-right">
  <div class="container">
    <h2>What Our Clients Say</h2>

    <?php if (count($testimonials) === 0): ?>
      <p class="muted">No approved testimonials yet.</p>
    <?php else: ?>
    <div id="testimonials-slider" class="glide">
      <div class="glide__track" data-glide-el="track">
        <ul class="glide__slides">
          <?php foreach ($testimonials as $t):
            $name   = $t['name'] ?? '';
            $msg    = $t['message'] ?? '';
            $rate   = (float)($t['rating'] ?? 0);
            $date   = pretty_date($t['created_at'] ?? '');
            $avatar = !empty($t['submitter_avatar_url']) ? $t['submitter_avatar_url'] : ($BASE.'assets/images/person1.jpg');
            $width  = max(0, min(100, ($rate/5)*100));
          ?>
          <li class="glide__slide">
            <div class="testimonial-card card">
              <div class="testimonial-header">
                <img src="<?= e($avatar) ?>" alt="" class="testimonial-avatar">
                <div class="testimonial-info">
                  <p class="author"><?= e($name) ?></p>
                  <div class="stars" style="position:relative;display:inline-block">
                    <span style="color:#d1d5db;letter-spacing:2px">★★★★★</span>
                    <span style="position:absolute;left:0;top:0;color:#f59e0b;white-space:nowrap;overflow:hidden;letter-spacing:2px;width:<?= $width ?>%">★★★★★</span>
                  </div>
                </div>
              </div>
              <p class="quote"><?= e($msg) ?></p>
              <div class="muted small"><?= e($date) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="glide__arrows" data-glide-el="controls">
        <button class="glide__arrow glide__arrow--left" data-glide-dir="<"><i class="fas fa-chevron-left"></i></button>
        <button class="glide__arrow glide__arrow--right" data-glide-dir=">"><i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="glide__bullets" data-glide-el="controls[nav]"></div>
    </div>
    <?php endif; ?>
  </div>
</section>




        <!-- About + Feedback form -->
        <section id="about" class="content-section" data-aos="zoom-in-up">
            <div class="container feedback-grid">
                <div class="about-text">
                    <h2>About <?= e($clinic_name) ?></h2>
                    <p>A digital platform designed to enhance pet care for the clients of Marawi Veterinary Clinic. We aim to replace inefficient manual processes with a streamlined system, fostering responsible pet ownership in the community.</p>
                </div>
                <div class="feedback-form">
                    <form class="card" id="feedbackForm" novalidate>
                        <h3>Leave a Review</h3>
                        <p>How was your experience? Let us know!</p>

                        <!-- FRACTIONAL RATING INPUT -->
                        <div class="rating-input" id="ratingInput" role="slider" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0" tabindex="0" aria-label="Rate 0 to 5">
                          <span class="bg">★★★★★</span>
                          <span class="fill" id="ratingFill">★★★★★</span>
                        </div>
                        <span class="rating-reading" id="ratingReading">(0.0)</span>

                        <input type="text"  id="feedbackName"    placeholder="Your Full Name" required>
                        <input type="email" id="feedbackEmail"   placeholder="Your Email Address" required>
                        <textarea id="feedbackMessage" placeholder="Your Message..." required rows="4"></textarea>
                        <button type="submit" class="btn">Submit Feedback</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-content">
            <div class="footer-section about-us">
                <h3><?= e($clinic_name) ?></h3>
                <p><?= e($footer_tagline) ?></p>
            </div>
            <div class="footer-section quick-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#announcements">Announcements</a></li>
                    <li><a href="#news">Pet Care Tips</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="<?= $BASE ?>auth/login">Login</a></li>
                    <li><a href="<?= $BASE ?>auth/signup">Sign Up</a></li>
                </ul>
            </div>
            <div class="footer-section contact-info">
                <h3>Contact Us</h3>
                <p><i class="fas fa-map-marker-alt"></i> <?= e($contact_addr) ?></p>
                <p><i class="fas fa-phone"></i> <?= e($contact_phone) ?></p>
                <p><i class="fas fa-envelope"></i> <?= e($contact_email) ?></p>
            </div>
            <div class="footer-section social-media">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <?php foreach ($social_links as $link): ?>
                        <a href="<?= e($link['url']) ?>" class="social-icon" target="_blank" rel="noopener noreferrer" aria-label="<?= e($link['platform']) ?>">
                            <i class="<?= e($link['icon_class']) ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= e($clinic_name) ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Shared modal for Announcements & Pet Care -->
    <div id="announcementModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3 id="modalTitle"></h3>
            <div id="modalImageContainer"></div>
            <div id="modalBody"></div>
        </div>
    </div>
</body>
</html>
