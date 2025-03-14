<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Marketing/Pricing Page
 */

// Include configuration files
require_once 'config/config.php';

// Page title
$pageTitle = "CertifyPro - Enterprise Certificate Management Solution";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 6rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%233b82f6' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--light-color);
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        
        .feature-card {
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .price-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .price-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .price-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .price-popular {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--warning-color);
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }
        
        .price-amount {
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
        }
        
        .price-period {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .price-features {
            padding: 2rem;
        }
        
        .price-feature {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .price-feature i {
            color: var(--success-color);
            margin-right: 0.5rem;
        }
        
        .price-cta {
            padding: 0 2rem 2rem;
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }
        
        .btn-outline {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background-color: transparent;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }
        
        .testimonial-card {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .testimonial-card:before {
            content: '"';
            position: absolute;
            top: 10px;
            left: 20px;
            font-size: 5rem;
            font-family: Georgia, serif;
            color: rgba(37, 99, 235, 0.1);
            line-height: 1;
        }
        
        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 4rem 0 2rem;
        }
        
        .footer-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-link:hover {
            color: white;
        }
        
        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .section-heading {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 3rem;
        }
        
        .section-heading:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }
        
        .section-heading.text-center:after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 4rem 0;
            }
            
            .price-card {
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-primary">Certify</span><span class="text-dark">Pro</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="features.php">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pricing.php">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="verify/">Verify Certificate</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-primary px-4" href="admin/">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-pattern"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Premium Certificate Management Solution</h1>
                    <p class="lead mb-4">CertifyPro provides enterprise-grade certificate issuance, management, and verification for organizations that need secure, reliable, and professional credentialing solutions.</p>
                    <div class="d-flex gap-3">
                        <a href="#pricing" class="btn btn-light btn-lg px-4">View Pricing</a>
                        <a href="demo.php" class="btn btn-outline-light btn-lg px-4">Request Demo</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="assets/img/hero-image.png" alt="CertifyPro Dashboard" class="img-fluid rounded-3 shadow-lg">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Key Features -->
    <section class="py-5 py-md-7 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="h1 fw-bold">Why Choose CertifyPro?</h2>
                <p class="lead text-muted">The most comprehensive certificate management solution on the market</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card bg-white text-center">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="h4 mb-3">Secure Verification</h3>
                        <p class="text-muted mb-0">Tamper-proof certificates with advanced encryption and unique identifiers that can be easily verified online.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card bg-white text-center">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3 class="h4 mb-3">Advanced Analytics</h3>
                        <p class="text-muted mb-0">Comprehensive dashboard with verification trends, certificate statuses, and usage patterns visualization.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card bg-white text-center">
                        <div class="feature-icon">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <h3 class="h4 mb-3">Modern Interface</h3>
                        <p class="text-muted mb-0">Intuitive, responsive design that works seamlessly across all devices, providing a premium user experience.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Pricing Section -->
    <section class="py-5 py-md-7" id="pricing">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="h1 fw-bold">Premium Solution, Unmatched Value</h2>
                <p class="lead text-muted">Enterprise-grade certificate management at a competitive price</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="price-card position-relative">
                        <span class="price-popular">MOST POPULAR</span>
                        <div class="price-header">
                            <h3 class="h2 mb-0">Enterprise Edition</h3>
                            <div class="price-amount">$700</div>
                            <div class="price-period">One-time payment</div>
                        </div>
                        <div class="price-features">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Advanced Analytics Dashboard</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Bulk Certificate Operations</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Advanced Search & Filtering</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Data Export (PDF, Excel, CSV)</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Detailed Verification Insights</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Modern, Professional Design</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Geolocation Tracking</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Device Analytics</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Theme Customization</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Email Notifications</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>QR Code Verification</span>
                                    </div>
                                    <div class="price-feature">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Dedicated Support</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="price-cta">
                            <a href="contact.php" class="btn btn-primary btn-lg w-100">Get Started Today</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <p class="mb-0">Need a custom solution? <a href="contact.php" class="text-primary fw-bold">Contact us</a> for tailored enterprise pricing.</p>
            </div>
        </div>
    </section>
    
    <!-- Testimonials -->
    <section class="py-5 py-md-7 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="h1 fw-bold">Trusted by Organizations Worldwide</h2>
                <p class="lead text-muted">See what our clients have to say about CertifyPro</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="mb-4">"CertifyPro has transformed how we manage our training certifications. The analytics dashboard gives us insights we never had before, and our students love the professional verification system."</p>
                        <div class="d-flex align-items-center">
                            <img src="assets/img/testimonial-1.jpg" alt="John Smith" class="testimonial-avatar">
                            <div>
                                <h5 class="mb-0">John Smith</h5>
                                <p class="text-muted mb-0">Training Director, Global Tech</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="mb-4">"The bulk operations feature alone saved us countless hours of manual work. The verification insights have also helped us improve our certification programs based on real data."</p>
                        <div class="d-flex align-items-center">
                            <img src="assets/img/testimonial-2.jpg" alt="Sarah Johnson" class="testimonial-avatar">
                            <div>
                                <h5 class="mb-0">Sarah Johnson</h5>
                                <p class="text-muted mb-0">Education Manager, LearnPlus</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="mb-4">"Our certificates now look professional and are easy to verify. The analytics dashboard has been invaluable for tracking our certification program's success and making improvements."</p>
                        <div class="d-flex align-items-center">
                            <img src="assets/img/testimonial-3.jpg" alt="David Chen" class="testimonial-avatar">
                            <div>
                                <h5 class="mb-0">David Chen</h5>
                                <p class="text-muted mb-0">CEO, Certificate Masters</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="py-5 py-md-7 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-center text-lg-start">
                    <h2 class="h1 fw-bold mb-3">Ready to Transform Your Certificate Management?</h2>
                    <p class="lead mb-0">Get started with CertifyPro today and experience the difference.</p>
                </div>
                <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                    <a href="contact.php" class="btn btn-light btn-lg px-4">Contact Sales</a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h4 class="fw-bold mb-4"><span class="text-primary">Certify</span><span class="text-white">Pro</span></h4>
                    <p class="mb-4">Enterprise-grade certificate validation system for organizations that need secure, reliable, and professional credentialing solutions.</p>
                    <div class="d-flex">
                        <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2">
                    <h5 class="fw-bold mb-4">Company</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="about.php" class="footer-link">About Us</a></li>
                        <li class="mb-2"><a href="features.php" class="footer-link">Features</a></li>
                        <li class="mb-2"><a href="pricing.php" class="footer-link">Pricing</a></li>
                        <li class="mb-2"><a href="contact.php" class="footer-link">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 col-lg-2">
                    <h5 class="fw-bold mb-4">Resources</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="blog.php" class="footer-link">Blog</a></li>
                        <li class="mb-2"><a href="docs.php" class="footer-link">Documentation</a></li>
                        <li class="mb-2"><a href="faq.php" class="footer-link">FAQ</a></li>
                        <li class="mb-2"><a href="support.php" class="footer-link">Support</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 col-lg-4">
                    <h5 class="fw-bold mb-4">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> 123 Certification Ave, Suite 101</li>
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> info@certifypro.com</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i> (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4 bg-light opacity-10">
            
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; 2023 CertifyPro. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="privacy.php" class="footer-link">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="terms.php" class="footer-link">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
