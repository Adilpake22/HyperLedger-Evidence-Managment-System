<?php
session_start();

if($SERVER["REQURST_METHOD"]== "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];


    div class="form-section">
                <div class="section-label">Complainant Details</div>
                <div class="form-grid">

                    <div class="field-group span-2">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="ComplainantName" id="txtComplainantName" placeholder="As per official ID" required>
                    </div>

                    <div class="field-group">
                        <label>Phone Number <span class="req">*</span></label>
                        <input type="text" name="ComplainantPhone" id="txtComplainantPhone" placeholder="10-digit number"
                               pattern="[0-9]{10}" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        <span class="field-hint">Digits only, no spaces or dashes</span>
                    </div>

                    <div class="field-group">
                        <label>Email Address <span class="req">*</span></label>
                        <input type="email" name="ComplainantEmail" id="txtComplainantEmail" placeholder="official@domain.gov.in" required>
                    </div>

                </div>
            </div>

            <!-- Section 3: Evidence Documents -->
            <div class="form-section">
                <div class="section-label">Evidence Documents</div>
                <div class="file-drop-area" id="dropArea">
                    <input type="file" name="DocumentPath[]" id="txtDocumentPath" multiple
                           accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileList(this.files)">
                    <div class="file-drop-icon">📂</div>
                    <div class="file-drop-text">Click to select files or drag &amp; drop here</div>
                    <div class="file-drop-sub">Accepted formats: PDF, JPG, PNG &nbsp;|&nbsp; Max 5 MB per file</div>
                </div>
                <div class="file-list" id="fileList"></div>
            </div>

    
}
