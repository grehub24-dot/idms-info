<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch system settings for dues and academic year
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('current_academic_year', 'annual_dues_amount')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$current_year = $settings['current_academic_year'] ?? '2025/2026';
$required_dues = number_format((float)($settings['annual_dues_amount'] ?? 100.00), 2);
?>

<div class="hero" style="height: 40vh; background: linear-gradient(rgba(0,51,102,0.8), rgba(0,51,102,0.8)), url('images/aamusted.jpg') center/cover;">
    <h1>Fees & Payment Schedule</h1>
    <p>Official guide for Department of IT Education students</p>
</div>

<div class="section">
    <div class="container">
        
        <!-- Navigation Tabs -->
        <div class="tabs" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #eee; overflow-x: auto;">
            <button class="tab-btn active" onclick="openTab(event, 'school-fees')" style="padding: 10px 20px; background: none; border: none; font-weight: bold; color: var(--primary-color); border-bottom: 3px solid var(--primary-color); cursor: pointer; white-space: nowrap;">School Fees</button>
            <button class="tab-btn" onclick="openTab(event, 'infotess-dues')" style="padding: 10px 20px; background: none; border: none; font-weight: bold; color: #666; cursor: pointer; white-space: nowrap;">INFOTESS Dues</button>
            <button class="tab-btn" onclick="openTab(event, 'payment-guide')" style="padding: 10px 20px; background: none; border: none; font-weight: bold; color: #666; cursor: pointer; white-space: nowrap;">How to Pay</button>
        </div>

        <!-- School Fees Tab -->
        <div id="school-fees" class="tab-content" style="display: block;">
            <h2 class="section-title" style="text-align: left;">Faculty of Applied Sciences and Mathematics Education (FASME)</h2>
            <div class="alert alert-info" style="background: #e3f2fd; color: #0c5460; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> Note: These are the provisional fees for the 2024/2025 Academic Year (Regular & Part-Time).
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 15px; text-align: left;">Programme Category</th>
                            <th style="padding: 15px; text-align: right;">Fresh Students</th>
                            <th style="padding: 15px; text-align: right;">2nd Year</th>
                            <th style="padding: 15px; text-align: right;">3rd Year</th>
                            <th style="padding: 15px; text-align: right;">Final Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Undergraduate -->
                        <tr style="background: #e9ecef; font-weight: bold;">
                            <td colspan="5" style="padding: 10px 15px;">Undergraduate Programmes (BSc/BEd/BA)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 15px;">
                                <strong>IT / Cyber Security</strong><br>
                                <small>B.Sc. IT, B.Sc. Cyber Security, B.Ed. Computing (AI/IOT), B.Ed. IT</small>
                            </td>
                            <td style="padding: 15px; text-align: right;">3,024.00</td>
                            <td style="padding: 15px; text-align: right;">2,411.00</td>
                            <td style="padding: 15px; text-align: right;">2,411.00</td>
                            <td style="padding: 15px; text-align: right;">3,083.00</td>
                        </tr>
                        
                        <!-- Diploma -->
                        <tr style="background: #e9ecef; font-weight: bold;">
                            <td colspan="5" style="padding: 10px 15px;">Diploma Programmes</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 15px;">
                                <strong>IT / Cyber Security</strong><br>
                                <small>Dip. IT, Dip. Cyber Security</small>
                            </td>
                            <td style="padding: 15px; text-align: right;">2,682.00</td>
                            <td style="padding: 15px; text-align: right; color: #999;">-</td>
                            <td style="padding: 15px; text-align: right; color: #999;">-</td>
                            <td style="padding: 15px; text-align: right;">2,468.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba;">
                <strong>Included Charges:</strong> SRC Dues, Examination Fees, Medical Exams, Sports, ICT. (Note: INFOTESS dues are paid separately).
            </div>
            
            <h3 class="section-title" style="text-align: left; margin-top: 40px;">Postgraduate Programmes</h3>
            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 15px; text-align: left;">Programme</th>
                            <th style="padding: 15px; text-align: left;">Duration</th>
                            <th style="padding: 15px; text-align: left;">Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 15px;">M. Phil. Information Technology</td>
                            <td style="padding: 15px;">2 Years</td>
                            <td style="padding: 15px;">Full-Time / Weekend</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd; background: #f9f9f9;">
                            <td style="padding: 15px;">M. Sc. Information Technology Education</td>
                            <td style="padding: 15px;">1 Year</td>
                            <td style="padding: 15px;">Weekend / Sandwich</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 15px;">M. Phil Information Technology (Top-up from M.Ed./M.Sc.)</td>
                            <td style="padding: 15px;">1 Year</td>
                            <td style="padding: 15px;">Weekend</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- INFOTESS Dues Tab -->
        <div id="infotess-dues" class="tab-content" style="display: none;">
            <h2 class="section-title" style="text-align: left;">INFOTESS Association Dues</h2>
            <div class="card" style="max-width: 600px; margin: 0 auto; text-align: center; border-top: 5px solid var(--secondary-color);">
                <div class="card-content">
                    <h3 style="color: var(--primary-color);"><?php echo htmlspecialchars($current_year); ?> Academic Year</h3>
                    <div style="font-size: 3rem; font-weight: bold; color: var(--secondary-color); margin: 20px 0;">GHS <?php echo $required_dues; ?></div>
                    <p style="margin-bottom: 20px;">Mandatory for all Department of IT Education students.</p>
                    
                    <ul style="text-align: left; margin-bottom: 30px; padding-left: 20px;">
                        <li><i class="fas fa-check-circle" style="color: green;"></i> Covers departmental souvenirs (T-shirts, Lacostes)</li>
                        <li><i class="fas fa-check-circle" style="color: green;"></i> Supports INFOTESS Week Celebration</li>
                        <li><i class="fas fa-check-circle" style="color: green;"></i> Funds tutorials and workshops</li>
                    </ul>

                    <a href="admin/payments.php" class="btn-primary">Pay Dues Now</a>
                </div>
            </div>
        </div>

        <!-- Payment Guide Tab -->
        <div id="payment-guide" class="tab-content" style="display: none;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                
                <!-- School Fees -->
                <div class="card">
                    <div class="card-content">
                        <h3><i class="fas fa-university"></i> How to Pay School Fees</h3>
                        <p style="margin-bottom: 15px;">Payment can be made at any branch of the following approved banks:</p>
                        <ul style="list-style: none;">
                            <li style="margin-bottom: 10px;"><i class="fas fa-angle-right"></i> <strong>GCB Bank</strong></li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-angle-right"></i> <strong>Ecobank</strong></li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-angle-right"></i> <strong>Zenith Bank</strong></li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-angle-right"></i> <strong>Consolidated Bank Ghana (CBG)</strong></li>
                        </ul>
                        <div class="alert alert-warning" style="margin-top: 15px; font-size: 0.9rem;">
                            <strong>Instruction:</strong> Mention "AAMUSTED School Fees" and provide your <strong>Student Index Number</strong> and <strong>Full Name</strong> to the teller.
                        </div>
                        
                        <h4 style="margin-top: 20px;">Mobile Money (Shortcode)</h4>
                        <p>Dial <strong>*887*18#</strong> on all networks and follow the prompts. Select "AAMUSTED" from the list.</p>
                    </div>
                </div>

                <!-- Dues Payment -->
                <div class="card">
                    <div class="card-content">
                        <h3><i class="fas fa-mobile-alt"></i> How to Pay INFOTESS Dues</h3>
                        <p style="margin-bottom: 15px;">Dues can be paid via the following channels:</p>
                        
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                            <strong>Option 1: Financial Secretary</strong><br>
                            Pay cash directly to the INFOTESS Financial Secretary at the Department Office.
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                            <strong>Option 2: Mobile Money</strong><br>
                            <strong>Number:</strong> 054-XXXX-XXX<br>
                            <strong>Name:</strong> INFOTESS AAMUSTED<br>
                            <strong>Ref:</strong> Your Index Number
                        </div>

                        <p style="margin-top: 15px; font-size: 0.9rem; color: #666;">* Ensure you receive an official receipt after payment.</p>
                    </div>
                </div>

            </div>

            <!-- Agents Section -->
            <div style="margin-top: 40px;">
                <h3 style="border-bottom: 2px solid #eee; padding-bottom: 10px;">Approved Collection Agents/Outlets</h3>
                <p>The following campus agents are authorized to assist with Transflow payments:</p>
                <table class="table" style="width: 100%; margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Agent/Outlet Name</th>
                            <th>Location</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Campus Post Office</td>
                            <td>Main Administration Block</td>
                            <td>03220-XXXXX</td>
                        </tr>
                        <tr>
                            <td>SRC Secretariat</td>
                            <td>SRC Union Building</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>Unity Hall Mart</td>
                            <td>Unity Hall Entrance</td>
                            <td>0555-XXXX-XXX</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].style.color = "#666";
        tablinks[i].style.borderBottom = "none";
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.style.color = "var(--primary-color)";
    evt.currentTarget.style.borderBottom = "3px solid var(--primary-color)";
    evt.currentTarget.classList.add("active");
}
</script>

<?php require_once 'includes/footer.php'; ?>