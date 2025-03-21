    <footer class="bg-dark text-white mt-5 pt-5 pb-3">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5>Canada Immigration Consultancy</h5>
                    <p>Your trusted partner for Canadian immigration services. Licensed consultants providing expert guidance for all visa types.</p>
                    <div class="social-icons">
                        <a href="#" class="text-white mr-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white mr-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white mr-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Visa Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo getBaseURL(); ?>/visa_types/work_visa.php" class="text-light">Work Visas</a></li>
                        <li><a href="<?php echo getBaseURL(); ?>/visa_types/study_visa.php" class="text-light">Study Permits</a></li>
                        <li><a href="<?php echo getBaseURL(); ?>/visa_types/express_entry.php" class="text-light">Express Entry</a></li>
                        <li><a href="<?php echo getBaseURL(); ?>/visa_types/family_sponsorship.php" class="text-light">Family Sponsorship</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo getBaseURL(); ?>/resources/news.php" class="text-light">Immigration News</a></li>
                        <li><a href="<?php echo getBaseURL(); ?>/resources/guides.php" class="text-light">Immigration Guides</a></li>
                        <li><a href="<?php echo getBaseURL(); ?>/resources/faq.php" class="text-light">FAQ</a></li>
                        <li><a href="<?php echo getBaseURL(); ?>/resources/blog.php" class="text-light">Blog</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt mr-2"></i> 123 Immigration Ave, Toronto, ON</p>
                        <p><i class="fas fa-phone mr-2"></i> (123) 456-7890</p>
                        <p><i class="fas fa-envelope mr-2"></i> info@canadaimmigration.com</p>
                    </address>
                    <a href="<?php echo getBaseURL(); ?>/contact/contact_form.php" class="btn btn-outline-light btn-sm">Send Message</a>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Canada Immigration Consultancy. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="<?php echo getBaseURL(); ?>/privacy-policy.php" class="text-light">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="<?php echo getBaseURL(); ?>/terms-of-service.php" class="text-light">Terms of Service</a></li>
                        <li class="list-inline-item"><a href="<?php echo getBaseURL(); ?>/sitemap.php" class="text-light">Sitemap</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Live Chat Widget -->
    <div id="live-chat-widget" class="live-chat-widget">
        <div class="chat-header">
            <h5>Live Support</h5>
            <button id="chat-toggle" class="chat-toggle"><i class="fas fa-minus"></i></button>
        </div>
        <div class="chat-body">
            <div id="chat-messages" class="chat-messages">
                <div class="message system">
                    <p>Welcome to Canada Immigration Consultancy! How can we help you today?</p>
                </div>
            </div>
            <div class="chat-input">
                <input type="text" id="chat-message-input" placeholder="Type your message...">
                <button id="chat-send"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        <button id="chat-button" class="chat-button">
            <i class="fas fa-comments"></i>
        </button>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <script src="<?php echo getBaseURL(); ?>/assets/js/main.js"></script>
    <?php if(isset($page_specific_js)): ?>
    <script src="<?php echo getBaseURL(); ?>/assets/js/<?php echo $page_specific_js; ?>"></script>
    <?php endif; ?>
</body>
</html> 