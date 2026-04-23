<?php
// form.php - Multi-Step Wizard
session_start();

// Security Check
if (!isset($_SESSION['registration_access']) || !isset($_SESSION['app_type'])) {
    header("Location: index.php");
    exit();
}

$appType = $_SESSION['app_type'];
$trxId = $_SESSION['trx_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - Pridens</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <div class="wizard-container">
        
        <!-- Header outside card -->
        <div class="wizard-header">
            <div class="brand-logo">
                <img src="assets/img/pridens.png" alt="Pridens" style="height: 45px;">
            </div>
            <div style="text-align: right;">
                <h3 style="color: var(--text-on-dark); font-size: 1.1rem; margin: 0;"><?= ($appType == 'new_agent') ? 'New Agent Registration' : 'Promotion Application' ?></h3>
                <span style="color: var(--text-muted); font-size: 0.85rem;">TRX: <?= htmlspecialchars($trxId) ?></span>
            </div>
        </div>

        <form action="process.php" method="POST" id="wizardForm">
            <input type="hidden" name="form_type" value="<?= $appType ?>">
            <input type="hidden" name="trx_id" value="<?= $trxId ?>">
            
            <div class="wizard-card">
                
                <!-- Steps Navigation -->
                <div class="steps-nav">
                    <?php if ($appType === 'new_agent'): ?>
                        <div class="step-item active">Basic Details</div>
                        <div class="step-item">Address</div>
                        <div class="step-item">Contact & Work</div>
                        <div class="step-item">Review</div>
                    <?php else: ?>
                         <div class="step-item active">Agent Details</div>
                         <div class="step-item">Position Selection</div>
                         <div class="step-item">Review</div>
                    <?php endif; ?>
                </div>

                <!-- Wizard Content -->
                <div class="wizard-content">
                    
                    <?php if ($appType === 'new_agent'): ?>
                        
                        <!-- STEP 1: Basic Details -->
                        <div class="step-pane active" id="step1">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label>Subscriber ID</label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="text" name="subscriber_id" id="subscriber_id" class="form-control" placeholder="PDTC-000000" required>
                                        <button type="button" id="btn-verify" class="btn btn-primary" style="white-space: nowrap;">
                                            <i class="fas fa-search"></i> Verify
                                        </button>
                                    </div>
                                    <small id="verify-msg" class="text-muted"></small>
                                </div>
                                
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="firstname" id="firstname" class="form-control" required readonly>
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="lastname" id="lastname" class="form-control" required readonly>
                                </div>
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" name="middlename" id="middlename" class="form-control" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Birthdate</label>
                                    <input type="date" name="birthdate" id="birthdate" class="form-control" required readonly>
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" id="gender" class="form-select" required style="pointer-events: none; background: #f8f9fa;">
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Marital Status</label>
                                    <select name="marital_status" id="marital_status" class="form-select" required style="pointer-events: none; background: #f8f9fa;">
                                        <option value="">Select...</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Address -->
                        <div class="step-pane" id="step2">
                            <div class="form-grid span-3">
                                <div class="form-group">
                                    <label>Province</label>
                                    <input type="text" name="province" id="province" class="form-control" required readonly>
                                </div>
                                <div class="form-group">
                                    <label>City/Municipality</label>
                                    <input type="text" name="city" id="city" class="form-control" required readonly>
                                </div>
                                <div class="form-group">
                                    <label>Barangay</label>
                                    <input type="text" name="barangay" id="barangay" class="form-control" required readonly>
                                </div>
                            </div>
                            <div class="form-grid" style="margin-top: 1.5rem;">
                                <div class="form-group full-width">
                                    <label>Complete Address</label>
                                    <input type="text" name="address_line" id="address_line" class="form-control" required readonly>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Contact & Work -->
                        <div class="step-pane" id="step3">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="number" name="contact" id="contact" class="form-control" placeholder="09xxxxxxxxx" required>
                                </div>
                                <div class="form-group">
                                    <label>Occupation</label>
                                    <input type="text" name="occupation" id="occupation" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Source of Income</label>
                                    <input type="text" name="source_of_income" id="source_of_income" class="form-control" required>
                                </div>
                                <div class="form-group full-width">
                                    <label>Upline / Referral Code</label>
                                    <input type="text" name="referral_code" id="referral_code" class="form-control" placeholder="AG-00000" required readonly style="background: #f8f9fa;">
                                </div>
                            </div>
                        </div>

                         <!-- STEP 4: Review & T&C -->
                         <div class="step-pane" id="step4">
                            <div style="text-align: center; padding: 2rem;">
                                <h2>Terms and Conditions</h2>
                                <p style="color: var(--text-muted); margin-bottom: 2rem;">Please read and agree to the Agent Agreement.</p>
                                
                                <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; height: 150px; overflow-y: scroll; text-align: left; margin-bottom: 20px; font-size: 0.85rem; border-radius: 4px;">
                                    <strong>AGENT AGREEMENT</strong><br><br>
                                    1. <strong>Representation:</strong> I agree to represent Pridens Trading Corp. with integrity and honesty.<br>
                                    2. <strong>Compliance:</strong> I will comply with all company policies and procedures.<br>
                                    3. <strong>Confidentiality:</strong> I will maintain the confidentiality of company and customer information.<br>
                                    4. <strong>Compensation:</strong> I understand the commission structure and payout terms.<br>
                                    5. <strong>Termination:</strong> The company reserves the right to terminate this agreement for violation of terms.<br>
                                    <br>
                                    (Full terms available in the admin panel or company handbook.)
                                </div>

                                <div class="form-check" style="display: inline-block; text-align: left;">
                                    <input class="form-check-input" type="checkbox" id="agree_terms" required>
                                    <label class="form-check-label" for="agree_terms">
                                        I have read and agree to the <strong>Terms and Conditions</strong>.
                                    </label>
                                </div>

                                <hr style="margin: 2rem 0;">
                                
                                <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"><i class="fas fa-check-circle"></i></div>
                                <h2>Ready to Submit?</h2>
                                <p style="color: var(--text-muted);">Ensure all details are correct. By submitting, you confirm your application.</p>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- PROMOTION STEPS -->
                        <!-- Keep simple for now -->
                         <div class="step-pane active" id="promo-step1">
                            <div class="form-group">
                                <label>Agent Code</label>
                                <div class="input-group" style="display:flex; gap:10px;">
                                    <input type="text" name="agent_code" id="promo_agent_code" class="form-control" placeholder="AG-202X-XXXXX" required>
                                    <button type="button" id="btn-verify-promo" class="btn btn-secondary"><i class="fas fa-search"></i> Verify</button>
                                </div>
                                <small id="promo-verify-msg" style="display: block; margin-top: 5px;"></small>
                            </div>
                            <div id="promo-agent-info" style="margin-top: 15px; display:none; padding: 10px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid var(--primary-color);">
                                <div style="font-size: 0.9rem; color: #666;">Current Agent:</div>
                                <div id="promo-agent-name" style="font-weight: bold; font-size: 1.1rem;"></div>
                                <div id="promo-agent-pos" style="font-size: 0.85rem; color: #888;"></div>
                            </div>
                         </div>
                         <div class="step-pane">
                            <div class="form-group">
                                <label>Select New Position</label>
                                <select name="position" class="form-select" required>
                                    <option value="Executive Manager">Executive Manager</option>
                                    <option value="Sales Manager">Sales Manager</option>
                                </select>
                            </div>
                         </div>
                         <div class="step-pane">
                            <div style="text-align: center;"><h2>Review Details</h2></div>
                         </div>
                    <?php endif; ?>

                </div>

                <!-- Footer / Actions -->
                <div class="wizard-footer">
                    <button type="button" class="btn btn-secondary btn-prev" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Previous Step
                    </button>
                    <button type="button" class="btn btn-primary btn-next">
                        Next Step <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-primary btn-submit" style="display: none;">
                        Submit Application <i class="fas fa-paper-plane"></i>
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- Logic Script -->
    <script>window.APP_TYPE = '<?= $appType ?>';</script>
    <script src="assets/js/form-wizard.js"></script>
    <script src="assets/js/agent-registration.js"></script>

</body>
</html>
