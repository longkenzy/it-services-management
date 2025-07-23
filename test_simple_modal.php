<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Simple Modal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Simple Modal</h2>
        <button type="button" class="btn btn-primary" onclick="testModal()">
            Test Modal
        </button>
        
        <div id="debug-info" class="mt-3"></div>
    </div>

    <!-- Modal tạo task triển khai -->
    <div class="modal fade" id="createDeploymentTaskModal" tabindex="-1" aria-labelledby="createDeploymentTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createDeploymentTaskModalLabel">
                        <i class="fas fa-plus-circle text-primary"></i> Tạo Task Triển Khai
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Modal test đơn giản</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="button" class="btn btn-primary">
                        <i class="fas fa-save"></i> Tạo Task
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testModal() {
            console.log('Testing modal...');
            
            const modalElement = document.getElementById('createDeploymentTaskModal');
            if (!modalElement) {
                console.error('Modal element not found');
                document.getElementById('debug-info').innerHTML = '<div class="alert alert-danger">Modal element not found</div>';
                return;
            }
            
            console.log('Modal element found:', modalElement);
            
            try {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                console.log('Modal shown successfully');
                document.getElementById('debug-info').innerHTML = '<div class="alert alert-success">Modal shown successfully</div>';
            } catch (error) {
                console.error('Error showing modal:', error);
                document.getElementById('debug-info').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html> 