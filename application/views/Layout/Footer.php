<footer class="main-footer">
    <strong>Copyright &copy; 2014-2026 <a href="https://www.aretegroup.in/">Arete.in</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 3.1.0
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->


<!-- jQuery -->
<script src="<?= base_url() ?>assets/plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?= base_url() ?>assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="<?= base_url() ?>assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="<?= base_url() ?>assets/plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="<?= base_url() ?>assets/plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="<?= base_url() ?>assets/plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="<?= base_url() ?>assets/plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="<?= base_url() ?>assets/plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="<?= base_url() ?>assets/plugins/moment/moment.min.js"></script>
<script src="<?= base_url() ?>assets/plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="<?= base_url() ?>assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="<?= base_url() ?>assets/plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="<?= base_url() ?>assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="<?= base_url() ?>assets/dist/js/adminlte.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="<?= base_url() ?>assets/dist/js/demo.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="<?= base_url() ?>assets/dist/js/pages/dashboard.js"></script>
<script>
    setTimeout(function () {
        document.querySelectorAll('.flash-msg').forEach(function (el) {
            el.classList.remove('show');
            el.classList.add('hide');
        });
    }, 3000); // 3 seconds
</script>

<script>
$(document).ready(function(){
  $('#example1').DataTable();
});
  </script>
<script>
$('#createForm').submit(function(e){
  e.preventDefault();

  $.ajax({
    url:'<?= base_url('TRS/add_ajax') ?>',
    data:$('#createForm').serialize(),
    type:'post',
    //async: false,
    dataType:'json',
    success:function(response){
    $('#createModal').modal('hide');
    $('#createForm')[0].reset();
  
   // $('#example1').DataTable().ajax.reload();
   window.location.href = "<?= base_url('TRS/list') ?>";
  // location.reload();
    },
    error: function(){
      alert('error');
    }
  });
});
</script>
<script>
function editFun(ticket_id){

  // hide all role sections
  $('.role-section').hide();

  // clear old values (IMPORTANT)
  $('#editForm')[0].reset();

  $.ajax({
    url: "<?= base_url('TRS/edit_ajax') ?>",
    type: "POST",
    data: { ticket_id: ticket_id },
    dataType: "json",

    success: function(res){
      if(!res.status){
        alert(res.msg);
        return;
      }

      let role = <?= (int)$this->session->userdata('role_id') ?>;
      let t = res.data;

      // common
      $('#edit_ticket_id').val(t.ticket_id);
      $('#edit_title').val(t.title);
      $('#edit_description').val(t.description);

      // USER
      if(role === 1){
        $('#userSection').show();
      }

      // DEVELOPER
      if(role === 2){
        $('#devSection').show();
        $('#edit_status_dev').val(t.status);
      }

      // ADMIN / IT HEAD
      if(role === 3){

      
        $('#adminSection').show();
  
  $('#edit_assigned').html(options);
$('#edit_assigned').val(t.assigned_engineer_id);
        $('#edit_status_admin').val(t.status);
      }

      $('#editModal').modal('show');
    }
  });
}
</script>



<script>
$('#editForm').on('submit', function(e){
  e.preventDefault(); // stop normal form submit

  $.ajax({
    url: "<?= base_url('TRS/update_ajax') ?>",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",

    success: function(res){
      if(!res.status){
        alert(res.msg);
        return;
      }

      $('#editModal').modal('hide');
      alert('Ticket updated successfully');

      // easiest way
      location.reload();
    }
  });
});
</script>


<script>
function history(ticket_id){
    $.ajax({
        url: "<?= base_url('Developer/history_by_ticket') ?>",
        type: "POST",
        data: { ticket_id: ticket_id },
        success: function (response) {
            $('#historyContainer').html(response);
            $('#historyModal').modal('show');
        },
        error: function () {
            alert('Failed to load history');
        }
    });
};
</script>
<script>
function assign(ticket_id) {

    $.ajax({
        url: "<?= base_url('TRS/edit_ajax') ?>",
        type: "POST",
        data: { ticket_id: ticket_id },
        dataType: "json",
        success: function (res) {

            if (res.status) {

                const modal = $('#assignModal');

                modal.find('#assign_ticket_id').val(res.data.ticket_id);
                modal.find('#edit_title').val(res.data.title);
                modal.find('#edit_description').val(res.data.description);
                  // build developers
        let options = '<option value="">-- Select Developer --</option>';
        options += `<option value="<?= $this->session->userdata('user_id') ?>">Assign to Me</option>`;

        $.each(res.developers, function (i, dev) {
            options += `<option value="${dev.user_id}">
                          ${dev.user_name}
                        </option>`;
        });

        modal.find('#edit_assigned').html(options);

        // 🔥 PRESELECTS
        modal.find('#edit_assigned').val(res.data.assigned_engineer_id);
       // modal.find('#edit_status_admin').val(res.data.status);


                modal.modal('show');
            }
        }
    });
}
</script>
<script>
$(document).on('submit', '#assignForm', function(e){
  e.preventDefault();

  $.ajax({
    url: "<?= base_url('TRS/update_ajax') ?>",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",

    success: function(res){
      if(!res.status){
        alert(res.msg);
        return;
      }

      $('#assignModal').modal('hide');
      alert('Assigned successfully');
      location.reload();
    }
  });
});

</script>
<script>
function reassign(ticket_id) {

    $.ajax({
        url: "<?= base_url('TRS/edit_ajax') ?>",
        type: "POST",
        data: { ticket_id: ticket_id },
        dataType: "json",
        success: function (res) {

            if (res.status) {

                const modal = $('#reassignModal');

                modal.find('#assign_ticket_id').val(res.data.ticket_id);
                modal.find('#edit_title').val(res.data.title);
                modal.find('#edit_description').val(res.data.description);
                  // build developers
        let options = '<option value="">-- Select Developer --</option>';
        

      $.each(res.developers, function (i, dev) {
    if (dev.user_id != <?= $this->session->userdata('user_id') ?>) {
        options += `<option value="${dev.user_id}">
                      ${dev.user_name}
                    </option>`;
    }
});

        modal.find('#edit_assigned').html(options);

        // 🔥 PRESELECTS
        modal.find('#edit_assigned').val(res.data.assigned_engineer_id);
       // modal.find('#edit_status_admin').val(res.data.status);


                modal.modal('show');
            }
        }
    });
}
</script>
<script>
function openLeaveModal(ticket_id) {

    $.ajax({
        url: "<?= base_url('TRS/edit_ajax') ?>",
        type: "POST",
        data: { ticket_id: ticket_id },
        dataType: "json",
        success: function (res) {

            if (!res.status) {
                alert('Unable to fetch ticket details');
                return;
            }

            const modal = $('#leaveModal');

            modal.find('#leave_ticket_id').val(res.data.ticket_id);
            modal.find('#leave_title').val(res.data.title);
            modal.find('#leave_description').val(res.data.description);

            modal.find('#leave_reason').val('');

            modal.modal('show');
        }
    });
}



$(document).on('submit', '#leaveForm', function (e) {
    e.preventDefault();
    $.ajax({
        url: "<?= base_url('TRS/do_leave_ajax') ?>",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (res) {

            if (!res.status) {
                alert(res.msg);
                return;
            }

            $('#leaveModal').modal('hide');
            alert('Ticket left successfully');
            location.reload();
        }
    });
});
</script>

<script>
$(document).on('submit', '#reassignForm', function(e){
  e.preventDefault();

  $.ajax({
    url: "<?= base_url('TRS/do_reassign_ajax') ?>",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",

    success: function(res){
      if(!res.status){
        alert(res.msg);
        return;
      }

      $('#reassignModal').modal('hide');
      alert('Reassigned successfully');
      location.reload();
    }
  });
});

</script>

<!--add developer/Admin-->

<script>
$('#adduserlist').on('submit', function(e){
  e.preventDefault();

  $.ajax({
    url: '<?= base_url('TRS/save_userlist_ajax') ?>',
    type: 'POST',
    data: $('#adduserlist').serialize(),
    dataType: 'json',

    success:function(response){

      if(response.status == true){
        $('#modal-success').modal('hide');
        $('#adduserlist')[0].reset();

        alert("Successfully added")
        window.location.href = "<?= base_url('TRS/user_list') ?>";
      }else{
        alert(response.message);
      }
    },

    error:function(){
      alert('Server error');
    }
  });
});
</script>


<!-- edit developer/admin -->

<script>
function editUser(user_id){

  $('#editUserForm')[0].reset();

  $.ajax({
    url: "<?= base_url('TRS/edit_userlist_ajax') ?>",
    type: "POST",
    data:{user_id:user_id},
    dataType:"json",

    success:function(res){

      if(!res.status){
        alert(res.msg);
        return;
      }

      let u = res.data;

      $('#edit_user_id').val(u.user_id);
      $('#edit_user_name').val(u.user_name);
      $('#edit_name').val(u.name);
      $('#edit_email').val(u.email);
      $('#edit_phone').val(u.phone);
      $('#edit_company').val(u.company_name);
      $('#edit_department').val(u.department);
      $('#edit_role_id').val(u.role_id);

      $('#editUserModal').modal('show');
    }
  });
}
</script>

<script>
$('#editUserForm').on('submit',function(e){
 e.preventDefault();

 $.ajax({
   url:"<?= base_url('TRS/update_userlist_ajax') ?>",
   type:"POST",
   data:$(this).serialize(),
   dataType:"json",

   success:function(res){

     if(res.status){
       alert(res.msg);
       $('#editUserModal').modal('hide');
       location.reload();
     }else{
       alert(res.msg);
     }
   }
 });
});
</script>



</body>
</html>