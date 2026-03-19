<?php
// MOCK Generator for Receipt
// In production, this would use TCPDF or DOMPDF
class ReceiptGenerator {
    public function generate($paymentId, $receiptNumber, $student, $amount, $date, $level = '', $class = '', $programme = '', $balance = 0, $academicYear = '', $semester = '', $paymentMethod = 'Mobile Money', $stream = '') {
        // Define the path where receipts will be stored
        $directory = __DIR__ . '/../receipts/';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        
        $filename = "receipt_" . $receiptNumber . ".html"; // Using HTML for simplicity in this demo, usually .pdf
        $filepath = $directory . $filename;

        // Create a simple HTML receipt
        // Convert local image to base64 to ensure it displays in email clients that block external images or when offline
        $logoPath = __DIR__ . '/../images/infotess.png';
        $logoData = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
        } else {
             $logoData = '../images/infotess.png'; // Fallback
        }

        $html = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Payment Receipt</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f8f9fa;
                    padding: 40px;
                    color: #333;
                }
                .receipt-container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    border: 1px solid #ddd;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .receipt-header {
                    text-align: center;
                    border-bottom: 3px solid #4F46E5; /* Blue line */
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .receipt-header h1 {
                    color: #800020; /* Maroon */
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                }
                .receipt-header p {
                    margin: 2px 0;
                    font-size: 14px;
                    color: #555;
                }
                .receipt-header h3 {
                    margin-top: 15px;
                    font-size: 18px;
                    font-weight: bold;
                    text-transform: uppercase;
                    color: #333;
                }
                .logo {
                    width: 80px;
                    height: auto;
                    margin-bottom: 10px;
                    display: block;
                    margin-left: auto;
                    margin-right: auto;
                }
                .row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 30px;
                }
                .col {
                    width: 48%;
                }
                .details-title {
                    font-weight: bold;
                    font-size: 16px;
                    margin-bottom: 10px;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 5px;
                }
                .details-item {
                    margin-bottom: 5px;
                    font-size: 14px;
                }
                .details-item strong {
                    display: inline-block;
                    width: 120px;
                    color: #555;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 12px;
                    text-align: left;
                }
                th {
                    background-color: #f8f9fa;
                    font-weight: bold;
                }
                .text-end {
                    text-align: right;
                }
                .footer-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                    margin-top: 40px;
                }
                .qr-section {
                    text-align: center;
                }
                .qr-code {
                    width: 100px;
                    height: 100px;
                    margin-bottom: 10px;
                }
                .signature-section {
                    text-align: center;
                    width: 200px;
                }
                .signature-line {
                    border-top: 1px solid #333;
                    margin-bottom: 5px;
                }
                .info-box {
                    background-color: #e0f7fa;
                    color: #006064;
                    padding: 15px;
                    border-radius: 4px;
                    font-size: 13px;
                    margin-top: 30px;
                    border: 1px solid #b2ebf2;
                }
                .status-badge {
                    position: absolute;
                    top: 160px;
                    right: 60px;
                    border: 2px solid #28a745;
                    color: #28a745;
                    padding: 5px 15px;
                    font-weight: bold;
                    font-size: 18px;
                    transform: rotate(-15deg);
                    opacity: 0.8;
                }
                .action-buttons {
                    max-width: 800px;
                    margin: 0 auto 20px auto;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }
                .btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-weight: bold;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                .btn-print {
                    background-color: #4F46E5;
                    color: white;
                }
                .btn-download {
                    background-color: #10B981;
                    color: white;
                }
                @media print {
                    .no-print {
                        display: none !important;
                    }
                    body {
                        background-color: white;
                        padding: 0;
                    }
                    .receipt-container {
                        box-shadow: none;
                        border: none;
                        padding: 0;
                        max-width: 100%;
                    }
                }
            </style>
        </head>
        <body>
            <div class='action-buttons no-print'>
                <button onclick='window.print()' class='btn btn-print'>🖨️ Print Receipt</button>
                <button onclick='downloadPDF()' class='btn btn-download'>📥 Download PDF</button>
            </div>
            <div class='receipt-container' id='receipt-content'>
                <div class='receipt-header'>
                    <img src='$logoData' alt='Logo' class='logo'>
                    <h1>INFOTESS IT DEPARTMENT</h1>
                    <p>Infotess.edu.gh, Kumasi, Ghana</p>
                    <p>Tel: +233 24 091 8031 | Email: info@infotess.edu</p>
                    <h3>OFFICIAL PAYMENT RECEIPT</h3>
                </div>

                <div class='status-badge'>PAID</div>

                <div class='row'>
                    <div class='col'>
                        <div class='details-title'>Receipt Details</div>
                        <div class='details-item'><strong>Receipt No:</strong> $receiptNumber</div>
                        <div class='details-item'><strong>Date:</strong> $date</div>
                        <div class='details-item'><strong>Payment Method:</strong> " . htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8') . "</div>
                    </div>
                    <div class='col text-end' style='text-align: right;'>
                        <div class='details-title' style='text-align: right;'>Student Details</div>
                        <div class='details-item'><strong>Name:</strong> {$student['full_name']}</div>
                        <div class='details-item'><strong>Index No:</strong> {$student['index_number']}</div>
                        " . (!empty($programme) ? "<div class='details-item'><strong>Department:</strong> " . htmlspecialchars($programme, ENT_QUOTES, 'UTF-8') . "</div>" : "<div class='details-item'><strong>Department:</strong> ICT</div>") . "
                        " . (!empty($level) ? "<div class='details-item'><strong>Level:</strong> " . htmlspecialchars($level, ENT_QUOTES, 'UTF-8') . "</div>" : "<div class='details-item'><strong>Level:</strong> 100</div>") . "
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th class='text-end'>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Infotess Dues Payment</td>
                            <td>" . (!empty($academicYear) ? htmlspecialchars($academicYear) : '2025/2026') . "</td>
                            <td>" . (!empty($semester) ? htmlspecialchars($semester) : 'Semester 1') . "</td>
                            <td class='text-end'>GHS " . number_format($amount, 2) . "</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan='3' class='text-end'>Total Amount Paid:</th>
                            <th class='text-end'>GHS " . number_format($amount, 2) . "</th>
                        </tr>
                        " . ($balance > 0 ? "
                        <tr>
                            <th colspan='3' class='text-end' style='color: red;'>Remaining Balance:</th>
                            <th class='text-end' style='color: red;'>GHS " . number_format($balance, 2) . "</th>
                        </tr>" : "") . "
                    </tfoot>
                </table>

                <div class='footer-row'>
                    <div class='qr-section'>
                        <img src='https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode("http://localhost/Infotess/verify_public.php?receipt=" . $receiptNumber) . "' class='qr-code' />
                        <p style='font-size: 12px; margin: 0;'>Scan to verify: $receiptNumber</p>
                    </div>
                    <div class='signature-section'>
                        <div class='signature-line'></div>
                        <div style='font-weight: bold;'>Authorized Signature</div>
                        <div style='font-size: 12px; color: #666;'>Finance Office</div>
                    </div>
                </div>

                <div class='info-box'>
                    <strong>ⓘ Information:</strong> This is an official digital receipt. Keep this for your records. You can access this receipt anytime from the payment records.
                </div>
            </div>
            
            <script src='https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js'></script>
            <script>
                function downloadPDF() {
                    const element = document.getElementById('receipt-content');
                    const opt = {
                        margin:       10,
                        filename:     'Receipt_$receiptNumber.pdf',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2, useCORS: true },
                        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                    };
                    html2pdf().set(opt).from(element).save();
                }
            </script>
        </body>
        </html>
        ";

        file_put_contents($filepath, $html);
        return $filename;
    }
}
?>
