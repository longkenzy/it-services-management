// Xử lý modal thông tin cá nhân cho mọi trang
if ($('#profileModal').length) {
  $(document).on('show.bs.modal', '#profileModal', function() {
      $.get('api/get_staff_detail.php?id=current', function(res) {
          if (res && res.success && res.data) {
              const d = res.data;
              $('#profileFullname').val(d.fullname || '');
              $('#profileEmail').val(d.email || d.email_work || '');
              $('#profilePhone').val(d.phone || d.phone_main || '');
              $('#profileBirth').val(d.birth_date || '');
              $('#profileGender').val(d.gender || '');
              $('#profileAddress').val(d.address || '');
          } else {
              if (typeof showAlert === 'function') showAlert('Không thể tải thông tin cá nhân!', 'danger');
          }
      }).fail(function() {
          if (typeof showAlert === 'function') showAlert('Lỗi khi tải thông tin cá nhân!', 'danger');
      });
  });

  $('#profileForm').on('submit', function(e) {
      e.preventDefault();
      const data = {
          fullname: $('#profileFullname').val().trim(),
          email_work: $('#profileEmail').val().trim(),
          phone_main: $('#profilePhone').val().trim(),
          birth_date: $('#profileBirth').val(),
          gender: $('#profileGender').val(),
          address: $('#profileAddress').val().trim()
      };
      $.ajax({
          url: 'api/update_profile.php',
          method: 'POST',
          data: JSON.stringify(data),
          contentType: 'application/json',
          dataType: 'json',
          success: function(res) {
              if (res && res.success) {
                  if (typeof showAlert === 'function') showAlert('Cập nhật thông tin thành công!', 'success');
                  $('#profileModal').modal('hide');
                  setTimeout(() => location.reload(), 1000);
              } else {
                  if (typeof showAlert === 'function') showAlert(res.message || 'Cập nhật thất bại!', 'danger');
              }
          },
          error: function() {
              if (typeof showAlert === 'function') showAlert('Lỗi khi cập nhật thông tin!', 'danger');
          }
      });
  });
} 