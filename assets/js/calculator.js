/**
 * JavaScript for CRS Calculator functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    const calculatorForm = document.getElementById('crs-calculator-form');
    
    if (calculatorForm) {
        // Handle spouse factors visibility
        const spouseRadios = document.querySelectorAll('input[name="has_spouse"]');
        const spouseFactors = document.getElementById('spouse-factors');
        
        spouseRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'yes' && this.checked) {
                    spouseFactors.style.display = 'block';
                    
                    // Make spouse fields required
                    const spouseInputs = spouseFactors.querySelectorAll('select, input');
                    spouseInputs.forEach(input => {
                        input.setAttribute('required', '');
                    });
                } else {
                    spouseFactors.style.display = 'none';
                    
                    // Remove required attribute from spouse fields
                    const spouseInputs = spouseFactors.querySelectorAll('select, input');
                    spouseInputs.forEach(input => {
                        input.removeAttribute('required');
                    });
                }
            });
        });
        
        // Handle education with Canadian credential visibility
        const educationSelect = document.getElementById('education');
        const canadianEducationSection = document.getElementById('canadian-education-section');
        
        if (educationSelect && canadianEducationSection) {
            educationSelect.addEventListener('change', function() {
                const selectedValue = parseInt(this.value);
                
                // Show Canadian education section for post-secondary education
                if (selectedValue >= 90) {
                    canadianEducationSection.style.display = 'block';
                } else {
                    canadianEducationSection.style.display = 'none';
                    
                    // Reset Canadian education radio buttons
                    const canadianEducationRadios = canadianEducationSection.querySelectorAll('input[type="radio"]');
                    canadianEducationRadios.forEach(radio => {
                        radio.checked = false;
                    });
                }
            });
        }
        
        // Form validation before submission
        calculatorForm.addEventListener('submit', function(event) {
            if (!calculatorForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Scroll to the first invalid element
                const firstInvalid = calculatorForm.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
                
                // Show validation messages
                calculatorForm.classList.add('was-validated');
            }
        });
        
        // Reset form handler
        const resetButton = calculatorForm.querySelector('button[type="reset"]');
        if (resetButton) {
            resetButton.addEventListener('click', function() {
                calculatorForm.classList.remove('was-validated');
                
                // Hide spouse factors section
                if (spouseFactors) {
                    spouseFactors.style.display = 'none';
                }
                
                // Hide Canadian education section
                if (canadianEducationSection) {
                    canadianEducationSection.style.display = 'none';
                }
                
                // Scroll to top of form
                calculatorForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }
    
    // For results page
    const saveResultButton = document.getElementById('save-result');
    if (saveResultButton) {
        saveResultButton.addEventListener('click', function() {
            // Check if user is logged in
            const isLoggedIn = document.body.classList.contains('logged-in');
            
            if (isLoggedIn) {
                // Save result to user profile via AJAX
                const score = document.getElementById('total-score').textContent;
                
                fetch('/calculators/save_result.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `score=${score}&calculator=crs`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const successMessage = document.createElement('div');
                        successMessage.className = 'alert alert-success mt-3';
                        successMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Your CRS score has been saved to your profile.';
                        
                        const resultActions = document.querySelector('.text-center.mt-3');
                        resultActions.parentNode.insertBefore(successMessage, resultActions.nextSibling);
                        
                        // Hide message after 5 seconds
                        setTimeout(() => {
                            successMessage.style.opacity = '0';
                            setTimeout(() => {
                                successMessage.remove();
                            }, 500);
                        }, 5000);
                    }
                })
                .catch(error => {
                    console.error('Error saving result:', error);
                });
            } else {
                // Redirect to login page
                window.location.href = '/user/login.php?redirect=calculators/crs_calculator.php';
            }
        });
    }
}); 