<?php
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Form Submit</title>
</head>
<body>
    <h2>Debug Form Submit Events</h2>
    
    <p>Mở Developer Tools (F12) và xem Console để debug form submit events.</p>
    
    <script>
    // Debug tất cả form submit events
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== DEBUG: Monitoring all form submit events ===');
        
        // Monitor tất cả form submit events
        document.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMIT EVENT DETECTED ===');
            console.log('Form ID:', e.target.id);
            console.log('Form action:', e.target.action);
            console.log('Submitter:', e.submitter);
            console.log('Submitter text:', e.submitter ? e.submitter.textContent : 'N/A');
            console.log('Event target:', e.target);
            console.log('Event currentTarget:', e.currentTarget);
            console.log('Event type:', e.type);
            console.log('Event bubbles:', e.bubbles);
            console.log('Event cancelable:', e.cancelable);
            console.log('Event defaultPrevented:', e.defaultPrevented);
            console.log('=== END FORM SUBMIT EVENT ===');
        }, true); // Use capture phase
        
        // Monitor tất cả button click events
        document.addEventListener('click', function(e) {
            if (e.target.tagName === 'BUTTON') {
                console.log('=== BUTTON CLICK EVENT DETECTED ===');
                console.log('Button text:', e.target.textContent);
                console.log('Button type:', e.target.type);
                console.log('Button onclick:', e.target.onclick);
                console.log('Button form:', e.target.form ? e.target.form.id : 'No form');
                console.log('=== END BUTTON CLICK EVENT ===');
            }
        });
    });
    </script>
    
    <p><a href="deployment_requests.php">Vào trang deployment_requests.php để test</a></p>
</body>
</html> 