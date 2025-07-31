<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * IT CRM - Dashboard Page
 * File: dashboard.php
 * Mục đích: Trang dashboard chính với thông tin công ty Smart Services
 * Tác giả: IT Support Team
 */

// Include các file cần thiết
require_once 'includes/session.php';

// Bảo vệ trang - yêu cầu đăng nhập
requireLogin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Kiểm tra nếu không có thông tin user (không bao giờ xảy ra nếu requireLogin() hoạt động đúng)
if (!$current_user) {
    redirectToLogin('Phiên đăng nhập không hợp lệ.');
}

// Lấy flash messages nếu có
$flash_messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>IT Services Management - Smart Services</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    
    <style>
        /* Slider Styles */
        .hero-slider {
            position: relative;
            width: 100%;
            height: 600px;
            overflow: hidden;
            margin-top: 0;
            margin-bottom: 2rem;
        }
        
        .slider-container {
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: opacity 0.8s ease-in-out;
            will-change: opacity;
            opacity: 0;
        }
        
        .slide.current {
            opacity: 1;
            z-index: 2;
        }
        
        .slide.next {
            opacity: 0;
            z-index: 1;
        }
        
        .slide.prev {
            opacity: 0;
            z-index: 0;
        }
        

        

        
        .slide-content {
            opacity: 1;
            transform: none;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .slide-content h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
            line-height: 1.2;
        }
        
        .slide-content .lead {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
            font-weight: 500;
        }
        
        .slide-content p {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
            opacity: 0.95;
        }
        
        .logo-container {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .slide-logo {
            max-width: 120px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
            transition: transform 0.3s ease;
        }
        
        .slide-logo:hover {
            transform: scale(1.05);
        }
        
        .slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        

        
        .slider-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        
        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            transform: scale(1);
        }
        
        .slider-dot:hover {
            background: rgba(255,255,255,0.8);
            transform: scale(1.2);
        }
        
        .slider-dot.active {
            background: white;
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(255,255,255,0.5);
        }
        
        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 15px 10px;
            cursor: pointer;
            font-size: 1.5rem;
            transition: background 0.3s ease;
            z-index: 10;
        }
        
        .slider-nav:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .slider-nav.prev {
            left: 20px;
        }
        
        .slider-nav.next {
            right: 20px;
        }
        
        /* Modern Card Styles */
        .modern-card {
            background: #667eea;
            color: white;
            border-radius: 0;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
            margin-bottom: 2rem;
            width: 100%;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }
        
        .section-title-white {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }
        
        .lead-text {
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .feature-list {
            margin-top: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .feature-item i {
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .icon-wrapper {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 2rem;
        }
        
        /* Value Cards */
        .value-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
            margin: 0 1rem;
        }
        
        .value-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #667eea;
        }
        
        .value-card.mission::before {
            background: #667eea;
        }
        
        .value-card.vision::before {
            background: #667eea;
        }
        
        .value-card.values::before {
            background: #667eea;
        }
        
        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            width: 80px;
            height: 80px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }
        
        .value-card h4 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .value-card p {
            color: #718096;
            line-height: 1.6;
        }
        
        /* Service Cards */
        .service-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
            margin: 0 0.5rem;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #667eea;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }
        
        .service-card h5 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .service-card p {
            color: #718096;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .service-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .feature-tag {
            background: #667eea;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* Contact Section */
        .contact-section {
            background: #667eea;
            color: white;
            border-radius: 0;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
            width: 100%;
            margin-bottom: 0;
        }
        
        .contact-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }
        
        .contact-info h3 i {
            margin-right: 1rem;
            color: #fbbf24;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .contact-item i {
            font-size: 1.5rem;
            margin-right: 1rem;
            margin-top: 0.2rem;
            color: #fbbf24;
            min-width: 20px;
        }
        
        .contact-item strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #fbbf24;
        }
        
        .contact-item p {
            margin: 0;
            line-height: 1.5;
        }
        
        .contact-visual {
            text-align: center;
        }
        
        .contact-visual i {
            font-size: 4rem;
            color: #fbbf24;
            margin-bottom: 1rem;
        }
        
        .contact-visual h5 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .contact-visual p {
            opacity: 0.9;
            line-height: 1.6;
        }
        
        /* Image styles */
        
        .company-image {
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
            transition: transform 0.3s ease;
        }
        
        .company-image:hover {
            transform: scale(1.02);
        }
        
        /* Full-width layout adjustments */
        .main-content {
            width: 100%;
            max-width: 100%;
            padding: 0;
            margin: 0;
            margin-bottom: 0;
        }
        
        .container-fluid {
            max-width: 100%;
            padding-left: 0;
            padding-right: 0;
        }
        
        .row {
            margin-left: 0;
            margin-right: 0;
        }
        
        .col-12, .col-md-4, .col-md-6, .col-md-8, .col-lg-3, .col-lg-4, .col-lg-8 {
            padding-left: 0;
            padding-right: 0;
        }
    </style>
</head>
<body>
    <?php 
    // Include header chung
    include 'includes/header.php'; 
    ?>
    
    <!-- Flash messages will be shown via JavaScript alert system -->
    
        <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">
        <!-- Hero Slider -->
        <div class="hero-slider">
            <div class="slider-container" id="sliderContainer">
                <!-- Slides will be loaded dynamically -->
            </div>
            
            <!-- Navigation buttons -->
            <button class="slider-nav prev" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-nav next" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- Dots indicator -->
            <div class="slider-controls" id="sliderDots">
                <!-- Dots will be generated dynamically -->
            </div>
        </div>
        
        <div class="container-fluid px-0 py-5 pb-0">
            
            <!-- Company Introduction -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="modern-card">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="intro-content">
                                    <h2 class="section-title-white mb-4">CÔNG TY TNHH CÔNG NGHỆ DỊCH VỤ SMART SERVICES</h2>
                                    <p class="lead-text mb-4">
                                        Smart Services là đơn vị hàng đầu trong lĩnh vực cung cấp các giải pháp công nghệ tích hợp và dịch vụ thông minh chuyên biệt cho doanh nghiệp.
                                    </p>
                                    <div class="feature-list">
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span>Đội ngũ chuyên gia 15+ năm kinh nghiệm</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span>Giải pháp công nghệ tiên tiến nhất</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span>Dịch vụ toàn diện từ tư vấn đến triển khai</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="intro-visual text-center">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-rocket"></i>
                                    </div>
                                    <h4 class="mt-3">Giải Pháp Toàn Diện</h4>
                                    <p class="text-muted">Từ tư vấn chiến lược đến phát triển phần mềm và quản lý vận hành tự động</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mission, Vision, Values -->
            <div class="row mb-5 px-4">
                <div class="col-12">
                    <h3 class="section-title text-center mb-5">Sứ Mệnh - Tầm Nhìn - Giá Trị</h3>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="value-card mission">
                        <div class="card-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h4>SỨ MỆNH</h4>
                        <p>Góp phần vào sự phát triển mạnh mẽ của ngành Dịch Vụ Công Nghệ Thông Tin ở Việt Nam, đồng thời tạo ra công ăn việc làm cho càng nhiều lao động càng tốt.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="value-card vision">
                        <div class="card-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h4>TẦM NHÌN</h4>
                        <p>Trở thành công ty hàng đầu trong lĩnh vực cung cấp Dịch Vụ công nghệ thông tin tại Việt Nam, tạo ra những gói Dịch Vụ mang tính đột phá.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="value-card values">
                        <div class="card-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>GIÁ TRỊ CỐT LÕI</h4>
                        <p><strong>Vui Vẻ, Lễ Phép, Chu Đáo</strong><br>Con người là tài sản quý giá nhất của chúng tôi, mỗi thành viên đều thực hành giá trị cốt lõi này.</p>
                    </div>
                </div>
            </div>
            
            <!-- Services Overview -->
            <div class="row mb-5 px-4">
                <div class="col-12">
                    <h3 class="section-title text-center mb-5">Dịch Vụ Chính</h3>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <h5>Tích Hợp Hạ Tầng</h5>
                        <p>Hệ thống máy chủ, mạng, trung tâm dữ liệu và bảo mật toàn diện</p>
                        <div class="service-features">
                            <span class="feature-tag">Server</span>
                            <span class="feature-tag">Network</span>
                            <span class="feature-tag">Security</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-digital-tachograph"></i>
                        </div>
                        <h5>Chuyển Đổi Số</h5>
                        <p>Giải pháp quản lý thông minh và tự động hóa quy trình doanh nghiệp</p>
                        <div class="service-features">
                            <span class="feature-tag">Digital</span>
                            <span class="feature-tag">Automation</span>
                            <span class="feature-tag">Smart</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h5>Dịch Vụ IT</h5>
                        <p>Onsite, bảo trì, nâng cấp và quản lý dự án CNTT chuyên nghiệp</p>
                        <div class="service-features">
                            <span class="feature-tag">Onsite</span>
                            <span class="feature-tag">Maintenance</span>
                            <span class="feature-tag">Support</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <h5>Cloud Services</h5>
                        <p>IaaS, DaaS và các dịch vụ đám mây chuyên nghiệp, bảo mật cao</p>
                        <div class="service-features">
                            <span class="feature-tag">IaaS</span>
                            <span class="feature-tag">DaaS</span>
                            <span class="feature-tag">Cloud</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="row mb-0">
                <div class="col-12">
                    <div class="contact-section">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="contact-info">
                                    <h3 class="mb-4">
                                        <i class="fas fa-map-marker-alt"></i>
                                        CÔNG TY TNHH CÔNG NGHỆ – DỊCH VỤ SMART SERVICES
                                    </h3>
                                    <div class="contact-item">
                                        <i class="fas fa-building"></i>
                                        <div>
                                            <strong>Địa chỉ:</strong>
                                            <p>Lầu 7, Tòa nhà MIOS Building, Số 121 Hoàng Hoa Thám, Phường Gia Định, Thành phố Hồ Chí Minh</p>
                                        </div>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <div>
                                            <strong>Hotline:</strong>
                                            <p>0937.009.737</p>
                                        </div>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <div>
                                            <strong>Email:</strong>
                                            <p>loclm@smartservices.com.vn</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 text-center">
                                <div class="contact-visual">
                                    <i class="fas fa-handshake"></i>
                                    <h5 class="mt-3">Đối Tác Tin Cậy</h5>
                                    <p>Đối tác đáng tin cậy của nhiều doanh nghiệp trong và ngoài nước</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    

    
    <!-- ===== SCRIPTS ===== -->
    
    <!-- jQuery -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="assets/js/alert.js"></script>
    <script src="assets/js/config.js"></script>
    
    <script>
        // Slider functionality
        let currentSlide = 0;
        let slides = [];
        let slideInterval;
        
        $(document).ready(function() {
            // Auto-hide any flash messages after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
            
            // Initialize slider
            initSlider();
        });
        
        function initSlider() {
            // Get all slider images from the folder
            loadSliderImages();
        }
        
                function loadSliderImages() {
            // Dynamic approach: check for all possible images
            const extensions = ['jpg', 'jpeg', 'png', 'webp'];
            const maxSlides = 20; // Check up to 20 slides
            
            let loadedImages = 0;
            let totalImagesToCheck = 0;
            let loadedSlides = [];
            
            // First, count how many images exist
            function checkImageExists(number, ext) {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = function() {
                        resolve(true);
                    };
                    img.onerror = function() {
                        resolve(false);
                    };
                    img.src = `assets/images/slider/image-slide${number}.${ext}`;
                });
            }
            
            // Check all possible combinations
            async function findAllImages() {
                const foundImages = [];
                
                for (let i = 1; i <= maxSlides; i++) {
                    const number = i.toString().padStart(2, '0'); // 01, 02, 03, etc.
                    
                    for (let ext of extensions) {
                        const exists = await checkImageExists(number, ext);
                        if (exists) {
                            foundImages.push({ number, ext });
                            break; // Found one extension, no need to check others
                        }
                    }
                }
                
                console.log(`Found ${foundImages.length} images:`, foundImages);
                
                // Now load all found images
                if (foundImages.length === 0) {
                    console.log('No images found, creating default slides');
                    createDefaultSlides();
                    return;
                }
                
                totalImagesToCheck = foundImages.length;
                
                foundImages.forEach((image, index) => {
                    const img = new Image();
                    
                    img.onload = function() {
                        loadedImages++;
                        loadedSlides[index] = {
                            src: `assets/images/slider/image-slide${image.number}.${image.ext}`
                        };
                        
                        console.log(`Image loaded: ${image.number}.${image.ext} (${loadedImages}/${totalImagesToCheck})`);
                        
                        // Create slides when all images are loaded
                        if (loadedImages === totalImagesToCheck) {
                            slides = loadedSlides.filter(slide => slide); // Remove any undefined entries
                            createSlides();
                        }
                    };
                    
                    img.onerror = function() {
                        loadedImages++;
                        console.log(`Image failed to load: ${image.number}.${image.ext} (${loadedImages}/${totalImagesToCheck})`);
                        
                        if (loadedImages === totalImagesToCheck) {
                            slides = loadedSlides.filter(slide => slide);
                            if (slides.length === 0) {
                                createDefaultSlides();
                            } else {
                                createSlides();
                            }
                        }
                    };
                    
                    img.src = `assets/images/slider/image-slide${image.number}.${image.ext}`;
                });
            }
            
            // Start the process
            findAllImages();
        }
        
        function createSlides() {
            const container = $('#sliderContainer');
            const dotsContainer = $('#sliderDots');
            
            // Clear existing content
            container.empty();
            dotsContainer.empty();
            
            // Create all slide containers
            slides.forEach((slide, index) => {
                let slideClass = 'prev'; // Default to prev (hidden left)
                
                if (index === 0) {
                    slideClass = 'current'; // First slide is current
                } else if (index === 1) {
                    slideClass = 'next'; // Second slide is next (hidden right)
                }
                // All other slides start as prev (hidden left)
                
                const slideContainer = $(`
                    <div class="slide ${slideClass}" 
                         style="background-image: url('${slide.src}')">
                        <div class="slide-overlay">
                            <div class="slide-content">
                                <div class="logo-container">
                                    <img src="assets/images/logo.png" alt="Smart Services Logo" class="slide-logo">
                                </div>
                                <h2>CÔNG TY TNHH CÔNG NGHỆ DỊCH VỤ SMART SERVICES</h2>
                                <p class="lead">Chào mừng bạn đến với Công Ty TNHH Công Nghệ – Dịch Vụ Smart Services</p>
                                <p>Công ty Dịch vụ CNTT uy tín hàng đầu tại Tp.HCM.</p>
                                <p>Liên hệ để được tư vấn hoặc báo giá.</p>
                            </div>
                        </div>
                    </div>
                `);
                container.append(slideContainer);
                
                // Create dot
                const dot = $(`<div class="slider-dot ${index === 0 ? 'active' : ''}" onclick="goToSlide(${index})"></div>`);
                dotsContainer.append(dot);
                
                // Debug: log slide creation
                console.log(`Created slide ${index + 1}:`, slide.src, `(${slideClass})`);
            });
            
            console.log('=== SLIDE ORDER CHECK ===');
            console.log('Expected order: Slide 1 (image-slide01.jpg) → Slide 2 (image-slide02.jpg) → Slide 3 (image-slide03.jpg)');
            slides.forEach((slide, index) => {
                console.log(`Slide ${index + 1}: ${slide.src}`);
            });
            console.log('========================');
            
            // Start auto-slide if we have multiple slides
            if (slides.length > 1) {
                console.log('Multiple slides detected, will start auto-slide in 1 second');
                // Start auto-slide after a short delay to ensure everything is loaded
                setTimeout(function() {
                    startAutoSlide();
                }, 1000);
            } else {
                console.log('Only one slide detected, no auto-slide needed');
            }
        }
        
        function createDefaultSlides() {
            slides = [
                {
                    src: 'assets/images/placeholder-news.svg'
                },
                {
                    src: 'assets/images/placeholder-news.svg'
                },
                {
                    src: 'assets/images/placeholder-news.svg'
                }
            ];
            createSlides();
        }
        
        function changeSlide(direction) {
            const totalSlides = slides.length;
            if (totalSlides === 0) return;
            
            const oldSlide = currentSlide + 1;
            
            // Calculate new slide index with proper wrapping
            currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
            
            // Ensure currentSlide is within bounds
            if (currentSlide < 0) currentSlide = totalSlides - 1;
            if (currentSlide >= totalSlides) currentSlide = 0;
            
            console.log(`Animation: Slide ${oldSlide} → Slide ${currentSlide + 1}`);
            showSlide(currentSlide);
        }
        
        function goToSlide(index) {
            if (index >= 0 && index < slides.length) {
                currentSlide = index;
                showSlide(currentSlide);
            }
        }
        
        function showSlide(index) {
            const totalSlides = slides.length;
            if (totalSlides === 0) return;
            
            // Calculate next slide index
            const nextIndex = (index + 1) % totalSlides;
            
            // Get current slide elements
            const currentSlide = $('.slide').eq(index);
            const nextSlide = $('.slide').eq(nextIndex);
            
            // First, set all slides to prev (hidden left)
            $('.slide').removeClass('current next prev').addClass('prev');
            
            // Set current slide to current (visible center)
            currentSlide.removeClass('prev').addClass('current');
            
            // Set next slide to next (hidden right)
            nextSlide.removeClass('prev').addClass('next');
            
            // Update dots
            $('.slider-dot').removeClass('active');
            $('.slider-dot').eq(index).addClass('active');
            
            // Debug: log current slide and positions
            console.log('Current slide:', index + 1, 'of', slides.length);
            console.log('Slide positions - Current:', index + 1, 'Next:', nextIndex + 1);
            console.log('Animation: Fade effect (current slide fades in, others fade out)');
            
            // Debug: check if any slides have conflicting opacity
            $('.slide').each(function(i) {
                const opacity = $(this).css('opacity');
                const classes = $(this).attr('class');
                console.log(`Slide ${i + 1} opacity:`, opacity, 'classes:', classes);
            });
        }
        
        function startAutoSlide() {
            // Don't start if already running
            if (slideInterval) {
                console.log('Auto-slide already running');
                return;
            }
            
            // Only start auto-slide if we have multiple slides
            if (slides.length > 1) {
                slideInterval = setInterval(function() {
                    changeSlide(1);
                }, 5000); // Change slide every 5 seconds
                console.log('Auto-slide started - changing every 5 seconds');
            }
        }
        
        function stopAutoSlide() {
            if (slideInterval) {
                clearInterval(slideInterval);
                slideInterval = null;
                console.log('Auto-slide stopped');
            }
        }
        
        // Auto-slide runs continuously without pause on hover
    </script>
</body>
</html> 