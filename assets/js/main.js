/**
 * Main JavaScript file for Canada Immigration Consultancy Website
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize Bootstrap popovers
    $('[data-toggle="popover"]').popover();
    
    // Live Chat Widget
    const chatButton = document.getElementById('chat-button');
    const chatContainer = document.getElementById('chat-container');
    const chatClose = document.getElementById('chat-close');
    const chatMessageInput = document.getElementById('chat-message-input');
    const chatSend = document.getElementById('chat-send');
    const chatMessages = document.getElementById('chat-messages');
    
    if (chatButton && chatContainer) {
        chatButton.addEventListener('click', function() {
            chatContainer.style.display = 'flex';
            chatButton.style.display = 'none';
        });
        
        if (chatClose) {
            chatClose.addEventListener('click', function() {
                chatContainer.style.display = 'none';
                chatButton.style.display = 'flex';
            });
        }
        
        if (chatSend && chatMessageInput && chatMessages) {
            chatSend.addEventListener('click', sendChatMessage);
            chatMessageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });
        }
    }
    
    function sendChatMessage() {
        const message = chatMessageInput.value.trim();
        if (message) {
            // Add user message
            const userMessageElement = document.createElement('div');
            userMessageElement.className = 'message user-message';
            userMessageElement.innerHTML = `
                <div class="message-content">${message}</div>
                <div class="message-time">${getCurrentTime()}</div>
            `;
            chatMessages.appendChild(userMessageElement);
            
            // Clear input
            chatMessageInput.value = '';
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Simulate response (in a real app, this would be an AJAX call to the server)
            setTimeout(function() {
                const botMessageElement = document.createElement('div');
                botMessageElement.className = 'message bot-message';
                botMessageElement.innerHTML = `
                    <div class="message-content">Thank you for your message. One of our immigration consultants will respond shortly.</div>
                    <div class="message-time">${getCurrentTime()}</div>
                `;
                chatMessages.appendChild(botMessageElement);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 1000);
        }
    }
    
    function getCurrentTime() {
        const now = new Date();
        let hours = now.getHours();
        let minutes = now.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        
        return `${hours}:${minutes} ${ampm}`;
    }
    
    // Sticky Header
    const header = document.querySelector('header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('sticky-header');
            } else {
                header.classList.remove('sticky-header');
            }
        });
    }
    
    // Form validation for all forms
    const forms = document.querySelectorAll('.needs-validation');
    if (forms.length > 0) {
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Spouse factors toggle in CRS calculator
    const spouseRadios = document.querySelectorAll('input[name="has_spouse"]');
    const spouseFactors = document.getElementById('spouse-factors');
    
    if (spouseRadios.length > 0 && spouseFactors) {
        spouseRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'yes' && this.checked) {
                    spouseFactors.style.display = 'block';
                } else {
                    spouseFactors.style.display = 'none';
                }
            });
        });
    }
    
    // File upload preview
    const fileInputs = document.querySelectorAll('.custom-file-input');
    if (fileInputs.length > 0) {
        fileInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const fileName = this.files[0].name;
                const label = this.nextElementSibling;
                label.textContent = fileName;
            });
        });
    }
    
    // Appointment booking calendar
    const dateInputs = document.querySelectorAll('.date-picker');
    if (dateInputs.length > 0) {
        dateInputs.forEach(function(input) {
            $(input).datepicker({
                format: 'yyyy-mm-dd',
                startDate: '+1d',
                autoclose: true,
                todayHighlight: true
            });
        });
    }
}); 