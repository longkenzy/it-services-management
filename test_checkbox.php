<!DOCTYPE html>
<html>
<head>
    <title>Test Checkbox</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test Checkbox Functionality</h1>
    
    <div>
        <label>Số hợp đồng PO:</label>
        <input type="text" id="po_number" placeholder="Nhập số hợp đồng PO">
        <div>
            <input type="checkbox" id="no_contract_po" name="no_contract_po">
            <label for="no_contract_po">Không có HĐ/PO</label>
        </div>
    </div>
    
    <script>
        // Test function
        function setupCheckboxHandler() {
            const checkbox = document.getElementById('no_contract_po');
            const poInput = document.getElementById('po_number');
            
            if (checkbox && poInput) {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        poInput.value = '';
                        poInput.disabled = true;
                        console.log('Checkbox checked - PO input disabled');
                    } else {
                        poInput.disabled = false;
                        console.log('Checkbox unchecked - PO input enabled');
                    }
                });
            }
        }
        
        // Setup khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            setupCheckboxHandler();
            console.log('Checkbox handler setup completed');
        });
    </script>
</body>
</html>

