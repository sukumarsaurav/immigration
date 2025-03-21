<?php include '../includes/header.php'; ?>

<main class="calculator-page">
    <div class="container">
        <h1>Express Entry CRS Score Calculator</h1>
        <p>Calculate your Comprehensive Ranking System (CRS) score for Express Entry immigration to Canada.</p>
        
        <form id="crs-calculator-form" method="post" action="crs_result.php">
            <div class="form-section">
                <h2>Core/Human Capital Factors</h2>
                
                <div class="form-group">
                    <label for="age">Age:</label>
                    <select name="age" id="age" required>
                        <option value="">Select your age</option>
                        <option value="0">Under 18</option>
                        <option value="99">18</option>
                        <option value="105">19</option>
                        <option value="110">20</option>
                        <!-- More age options -->
                        <option value="0">45 and above</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="education">Level of Education:</label>
                    <select name="education" id="education" required>
                        <option value="">Select your highest level of education</option>
                        <option value="0">Less than secondary school</option>
                        <option value="30">Secondary diploma</option>
                        <option value="90">One-year degree, diploma or certificate</option>
                        <option value="98">Two-year program</option>
                        <option value="120">Bachelor's degree</option>
                        <option value="128">Two or more certificates, diplomas, or degrees. One must be for a program of three years or longer</option>
                        <option value="135">Master's degree</option>
                        <option value="150">Doctoral degree</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Official Languages Proficiency:</label>
                    
                    <div class="language-section">
                        <h3>First Official Language (English or French)</h3>
                        
                        <div class="language-skills">
                            <div class="skill">
                                <label for="first_reading">Reading:</label>
                                <select name="first_reading" id="first_reading" required>
                                    <option value="">Select CLB level</option>
                                    <option value="0">Less than CLB 4</option>
                                    <option value="6">CLB 4</option>
                                    <option value="6">CLB 5</option>
                                    <option value="8">CLB 6</option>
                                    <option value="16">CLB 7</option>
                                    <option value="22">CLB 8</option>
                                    <option value="29">CLB 9</option>
                                    <option value="32">CLB 10 or higher</option>
                                </select>
                            </div>
                            
                            <!-- Similar dropdowns for Writing, Speaking, Listening -->
                        </div>
                    </div>
                    
                    <div class="language-section">
                        <h3>Second Official Language (if applicable)</h3>
                        <!-- Similar structure as first language -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="experience">Canadian Work Experience:</label>
                    <select name="experience" id="experience" required>
                        <option value="">Select years of experience</option>
                        <option value="0">None or less than a year</option>
                        <option value="40">1 year</option>
                        <option value="53">2 years</option>
                        <option value="64">3 years</option>
                        <option value="72">4 years</option>
                        <option value="80">5 years or more</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Spouse or Common-Law Partner Factors (if applicable)</h2>
                
                <div class="form-group">
                    <label>Do you have a spouse or common-law partner who will immigrate with you to Canada?</label>
                    <div class="radio-group">
                        <input type="radio" id="spouse-yes" name="has_spouse" value="yes">
                        <label for="spouse-yes">Yes</label>
                        
                        <input type="radio" id="spouse-no" name="has_spouse" value="no" checked>
                        <label for="spouse-no">No</label>
                    </div>
                </div>
                
                <div id="spouse-factors" style="display: none;">
                    <!-- Spouse education, language, and Canadian work experience fields -->
                </div>
            </div>
            
            <div class="form-section">
                <h2>Skill Transferability Factors</h2>
                <!-- Education + Language, Education + Experience, Foreign Work Experience + Language, etc. -->
            </div>
            
            <div class="form-section">
                <h2>Additional Points</h2>
                <!-- Provincial nomination, job offer, Canadian education, sibling in Canada, French language skills -->
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Calculate My CRS Score</button>
                <button type="reset" class="btn btn-secondary">Reset Form</button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide spouse factors based on selection
    const spouseRadios = document.querySelectorAll('input[name="has_spouse"]');
    const spouseFactors = document.getElementById('spouse-factors');
    
    spouseRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'yes') {
                spouseFactors.style.display = 'block';
            } else {
                spouseFactors.style.display = 'none';
            }
        });
    });
    
    // Form validation and dynamic score calculation will be added here
});
</script>

<?php include '../includes/footer.php'; ?> 