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
<script>
var allowedTransitions = <?= json_encode($permissions); ?>;
</script>
<script>
    setTimeout(function () {
        document.querySelectorAll('.flash-msg').forEach(function (el) {
            el.classList.remove('show');
            el.classList.add('hide');
        });
    }, 5000); // 5 seconds
</script>

<?php if (isset($devBarData) && !empty($devBarData)) { ?>
<script>
$(function () {

<?php foreach ($devBarData as $i => $dev): ?>

<?php if (
  $dev['open_cnt']==0 &&
  $dev['process_cnt']==0 &&
  $dev['resolved_cnt']==0 &&
  $dev['closed_cnt']==0
){ ?>

  if (document.getElementById('pie<?= $i ?>')) {
    document.getElementById('pie<?= $i ?>').parentElement.innerHTML =
      "<h4 style='text-align:center;color:#999'>No Tickets</h4>";
  }

<?php } else { ?>

  if (document.getElementById('pie<?= $i ?>')) {
    new Chart(document.getElementById('pie<?= $i ?>'), {
      type: 'pie',
      data: {
        labels: ['Open','In Process','Resolved','Closed'],
        datasets: [{
          data: [
            <?= (int)$dev['open_cnt'] ?>,
            <?= (int)$dev['process_cnt'] ?>,
            <?= (int)$dev['resolved_cnt'] ?>,
            <?= (int)$dev['closed_cnt'] ?>
          ],
          backgroundColor: ['#28a745','#ffc107','#17a2b8','#dc3545']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
  }

<?php } ?>

<?php endforeach; ?>

}); // ✅ CLOSES $(function)
</script>
<?php } ?>

<script>
$(document).ready(function(){

    if ($.fn.DataTable && $('#example1').length) {
        $('#example1').DataTable();
    }

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
      //alert('error');
       window.location.href = "<?= base_url('TRS/list') ?>";
    }
  });
});
</script>
<script>
function editFun(ticket_id){

  $('.role-section').hide();
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

      // COMMON FIELDS
      $('#edit_ticket_id').val(t.ticket_id);
      $('#edit_title').val(t.title);
      $('#edit_description').val(t.description);

      // 🔥 CLEAR OLD TASKS
      let wrapper = $('#editTaskWrapper');
      wrapper.html('');

      // 🔥 APPEND TASKS
      if(res.tasks && res.tasks.length > 0){

        $.each(res.tasks, function(i, task){

          wrapper.append(`
            <div class="input-group mb-2">
              <input type="text" name="tasks[]" 
                     class="form-control"
                     value="${task.task_title}">
              <div class="input-group-append">
                <button type="button" 
                        class="btn btn-danger removeTask">X</button>
              </div>
            </div>
          `);

        });

      } else {

        wrapper.append(`
          <div class="input-group mb-2">
            <input type="text" name="tasks[]" 
                   class="form-control"
                   placeholder="Enter Task">
          </div>
        `);
      }

      // USER
      if(role === 1){
        $('#userSection').show();
      }

      // DEVELOPER
      if(role === 2){
        $('#devSection').show();
        $('#edit_status_dev').val(t.status_id);
      }

      // ADMIN
      if(role === 3){
        $('#adminSection').show();
        $('#edit_assigned').val(t.assigned_engineer_id);
        $('#edit_status_admin').val(t.status_id);
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
          // ===== BUILD TASKS =====
        let taskWrapper = modal.find('#taskWrapper');
        taskWrapper.empty();

        if (res.tasks && res.tasks.length > 0) {

    res.tasks.forEach(function(task){

        let badge = task.is_completed == "1"
            ? '<span class="badge bg-success ms-2">Completed</span>'
            : '<span class="badge bg-warning ms-2">Pending</span>';

        taskWrapper.append(`
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <span>${task.task_title}</span>
                ${badge}
            </div>
        `);
    });

        } else {
            taskWrapper.append(`<div class="text-muted">No Tasks</div>`);
        }
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
       // modal.find('#edit_status_admin').val(res.data.status_id);


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
                // ===== BUILD TASKS =====
        let taskWrapper = modal.find('#taskWrapper');
        taskWrapper.empty();

        if (res.tasks && res.tasks.length > 0) {

            res.tasks.forEach(function(task){

                let badge = task.is_completed == "1"
                    ? '<span class="badge bg-success ms-2">Completed</span>'
                    : '<span class="badge bg-warning ms-2">Pending</span>';

                taskWrapper.append(`
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                        <span>${task.task_title}</span>
                        ${badge}
                    </div>
                `);
            });

        } else {
            taskWrapper.append(`<div class="text-muted">No Tasks</div>`);
        }

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

        let taskWrapper = modal.find('#taskWrapper');
        taskWrapper.empty();

        // 🔥 CORRECT PROPERTY
        if (res.tasks && res.tasks.length > 0) {

            res.tasks.forEach(function(task){
                taskWrapper.append(`
                    <div class="input-group mb-2">
                        <input type="text"
                            class="form-control"
                            value="${task.task_title}"
                            readonly>
                    </div>
                `);
            });

        } else {

            taskWrapper.append(`
                <div class="input-group mb-2">
                    <input type="text"
                        class="form-control"
                        value="No Tasks"
                        readonly>
                    </div>
            `);
        }

        modal.modal('show');
    }
    });
}



$(document).on('submit', '#leaveForm', function (e) {
    e.preventDefault();
    submitbtn.prop('disabled', true).text('Processing...');

    $.ajax({
        url: "<?= base_url('TRS/do_leave_ajax') ?>",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (res) {

            if (!res.status) {
                alert(res.msg);
                 submitbtn.prop('disabled', false).text('leave');
                return;
            }

            $('#leaveModal').modal('hide');
            submitbtn.prop('disabled', false).text('Leave');
            alert('Ticket left successfully');
            location.reload();
        }
    });
});
</script>

<script>
$(document).on('submit', '#reassignForm', function(e){
    e.preventDefault();

    let form = $(this);
    let submitBtn = form.find('button[type="submit"]');

    submitBtn.prop('disabled', true).text('Processing...');

    $.ajax({
        url: "<?= base_url('TRS/do_reassign_ajax') ?>",
        type: "POST",
        data: form.serialize(),
        dataType: "json",

        success: function(res){

            if(!res.status){
                alert(res.msg);
                submitBtn.prop('disabled', false).text('Reassign');
                return;
            }

            let taskWrapper = $('#taskWrapper');
            taskWrapper.empty();

            if (res.tasks && res.tasks.length > 0) {

                res.tasks.forEach(function(task){

                    let badge = task.is_completed == "1"
                        ? '<span class="badge bg-success ms-2">Completed</span>'
                        : '<span class="badge bg-warning ms-2">Pending</span>';

                    taskWrapper.append(`
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <span>${task.task_title}</span>
                            ${badge}
                        </div>
                    `);
                });

            } else {
                taskWrapper.append(`<div class="text-muted">No Tasks</div>`);
            }

            alert('Reassigned successfully');
            submitBtn.prop('disabled', false).text('Reassign');
            location.reload();
            
        },

        error: function(){
            alert('Something went wrong');
            submitBtn.prop('disabled', false).text('Reassign');
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

<script>
function viewMoreTickets(dev_id,dev_name){
// 🔥 SET MODAL TITLE HERE
  $('#devTicketsModal .modal-title').text(dev_name + ' - Tickets');
  $.ajax({
    url: "<?= base_url('Developer/getDeveloperTickets') ?>",
    type: "POST",
    data: { developer_id: dev_id },
    success: function(response){

      $('#devModalBody').html(response);
      $('#devTicketsModal').modal('show');

    }
  });

}
</script>


//profile available combos
<script>

let currentPage = 0;
const perPage = 8;

const avatars = document.querySelectorAll(".avatar-box");

function showPage() {

    avatars.forEach((box, index) => {
        box.style.display = "none";
    });

    let start = currentPage * perPage;
    let end = start + perPage;

    for(let i = start; i < end && i < avatars.length; i++) {
        avatars[i].style.display = "flex";
        avatars[i].style.flexDirection = "column";
        avatars[i].style.alignItems = "center";
    }
}

function nextPage() {
    if((currentPage + 1) * perPage < avatars.length) {
        currentPage++;
        showPage();
    }
}

function prevPage() {
    if(currentPage > 0) {
        currentPage--;
        showPage();
    }
}

showPage(); // initial load

</script>
<script>

var currentRoleId = <?= $this->session->userdata('role_id'); ?>;

/* ================================
   INIT KANBAN SORTABLE FUNCTION
================================ */

function initKanbanSortable() {

    // Destroy old sortable if exists
    if ($(".kanban-column").hasClass("ui-sortable")) {
        $(".kanban-column").sortable("destroy");
    }

    if (currentRoleId != 1) {

        $(".kanban-column").sortable({
            connectWith: ".kanban-column",
            placeholder: "ui-state-highlight",
            forcePlaceholderSize: true,
            tolerance: "pointer",
            cursor: "grabbing",
            opacity: 0.8,
            revert: 150,

            start: function (event, ui) {
                ui.item.addClass("dragging");
                ui.item.data("from-status", $(this).data("status"));
            },

            stop: function (event, ui) {
                ui.item.removeClass("dragging");
            },

            update: function (event, ui) {

                var newColumn = $(this);
                var to_status = parseInt(newColumn.data("status"));
                var from_status = parseInt(ui.item.data("from-status"));

                var allowed = true;
                
                // ❌ Block Open → Closed
            if (from_status === 1 && to_status === 4) {
                //$(this).sortable("cancel");
                allowed=false;
                
            }
                // ❌ Block Open → Resolved
            if (from_status === 1 && to_status === 3) {
                //$(this).sortable("cancel");
                allowed=false;
                
            }
                // Block In Process → Open
                if (from_status === 2 && to_status === 1) {
                    allowed = false;
                }

                // Block move to Resolved if not allowed
                if (to_status === 3 && ui.item.data("can-resolve") != 1) {
                    allowed = false;
                }

                if (!allowed) {
                    if (ui.sender) {
                        $(ui.sender).sortable("cancel");
                    } else {
                        $(this).sortable("cancel");
                    }
                    return;
                }

                var order = [];

                newColumn.children(".ticket-card").each(function(index){
                    order.push({
                        ticket_id: $(this).data("id"),
                        board_position: index
                    });
                });

                $.ajax({
                    url: "update_board_position",
                    type: "POST",
                    dataType: "json",
                    data: {
                        status_id: to_status,
                        order: JSON.stringify(order)
                    }
                });
            }
        });

    } else {
    $(".kanban-column").sortable({
        connectWith: ".kanban-column",
        placeholder: "ui-state-highlight",
        tolerance: "pointer",

        start: function (event, ui) {
            ui.item.data("from-status", $(this).data("status"));
        },

        update: function (event, ui) {

            var from_status = parseInt(ui.item.data("from-status"));
            var to_status   = parseInt($(this).data("status"));

            var ticket_id = ui.item.data("id");

            // 🔒 Allow only Closed → Open
            if (!(from_status === 4 && to_status === 1)) {
                $(this).sortable("cancel");
                
            }
            // ❌ Block Open → Closed
            if (from_status === 1 && to_status === 4) {
                $(this).sortable("cancel");
                
            }
            $.ajax({
                url: base_url + "TRS/reopen_ticket",
                type: "POST",
                data: {
                    ticket_id: ticket_id
                },
                success: function(res){
                    //location.reload();
                }
            });
        }
    });
}

    // Disable Closed column
    $(".kanban-column").each(function(){
        if($(this).data("status") == 4 && currentRoleId == 2){
            $(this).sortable("disable");
        }
    });
}


/* ================================
   PAGE LOAD
================================ */

$(document).ready(function(){
    initKanbanSortable();
});


/* ================================
   REFRESH BUTTON
================================ */

$(document).on('click', '#refreshBoard', function(){

    var btn = $(this);
    btn.html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        url: window.location.href,
        type: "GET",
        success: function(response){

            var newContent = $(response).find('#mainContent').html();
            $('#mainContent').html(newContent);

            // 🔥 IMPORTANT
            initKanbanSortable();
        },
        complete: function(){
            btn.html('🔄 Refresh');
        }
    });

});

</script>
<script>

var currentTicketId = null;
var base_url = "<?= base_url(); ?>";

/* =========================================
   TICKET CLICK → LOAD DETAILS
========================================= */

$(document).on('click', '.ticket-card', function() {

    var ticket_id = $(this).data('id');

    $('#ticketModal').modal('show');
    $('#ticketDetailContent').html("Loading...");

    $.ajax({
        url: base_url + "TRS/get_ticket_details",
        type: "POST",
        data: { ticket_id: ticket_id },
        dataType: "json",
        success: function(response) {
           
            var ticket = response.ticket;
            var tasks  = response.tasks;
            console.log(tasks);
            console.log("Slug:", ticket.status_slug);
    console.log("Role:", currentRoleId);
            currentTicketId = ticket.ticket_id;

            /* ---------- BUILD MODAL HTML ---------- */
            var tasks_title = '';

if(tasks && tasks.length > 0){
    tasks.forEach(function(t){
        tasks_title += `<div>• ${t.task_title}</div>`;
    });
} else {
    tasks_title = `<div class="text-muted">No tasks added</div>`;
}

            var html = `
            <div class="px-3 py-2">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">
                        <span class="badge badge-secondary mr-2">
                            #${ticket.ticket_id}
                        </span>
                        ${ticket.title}
                    </h4>
                    <span class="badge badge-success px-3 py-2">
                        ${ticket.status_name}
                    </span>
                </div>

                <hr>

                <div class="mb-2">
                    <p class="text-muted">Description</p>
                   <h6 class="text-muted mt-3">Tasks</h6>
${tasks_title}
                </div>

                <div class="mb-3 text-muted">
                    <small>
                        <strong>Handled By:</strong>
                        ${ticket.handled_by_name 
                            ? ticket.handled_by_name 
                            : 'Not Assigned'}
                    </small>
                </div>

                <div class="progress mb-2" style="height:18px;">
                    <div id="taskProgressBar"
                         class="progress-bar bg-success"
                         style="width:0%">
                    </div>
                </div>

                <small id="taskRatio" class="text-muted"></small>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">
                Tasks <span id="taskCount">(0/0)</span>
            </h5>
            <button class="btn btn-sm btn-primary" id="addTaskBtn">
                + Add Task
            </button>
                </div>

                        <div id="taskSection"></div>

                    </div>
                    `;

                    $('#ticketDetailContent').html(html);
                    // 🔥 SHOW CONFIRMATION ONLY FOR USER WHEN STATUS = RESOLVED
            if(ticket.status_slug === 'resolved' && currentRoleId == 1){

            $('#ticketDetailContent').prepend(`
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <span><strong>Is your issue resolved?</strong></span>
                    <div>
                <button class="btn btn-success btn-sm confirm-btn" data-answer="yes">Yes</button>
                <button class="btn btn-danger btn-sm confirm-btn" data-answer="no">No</button>
                    </div>
                </div>
            `);

}

            /* ---------- RENDER TASKS ---------- */

         tasks.forEach(function(task) {

                var taskHtml = `
                <div class="task-card mb-2" data-task-id="${task.task_id}">

                    <div class="d-flex justify-content-between align-items-center">

                        <div class="d-flex align-items-center">

                            <input type="checkbox"
                                   class="task-checkbox mr-2"
                                   data-id="${task.task_id}"
                                   ${task.is_completed == 1 ? 'checked' : ''}
                                   ${currentRoleId == 1 ? 'disabled' : ''}>

                            <span class="task-title"
                                  data-id="${task.task_id}">
                                  ${task.task_title}
                            </span>
                        </div>
                        
                       <div>
                  <button class="btn btn-sm btn-light edit-task"
                          data-id="${task.task_id}">
                      ✏
                  </button>
              </div></div>
                 

                    <!-- CHAT BOX -->
            <div class="chat-box mb-2" id="chat-${task.task_id}"></div>

            <!-- CHAT INPUT -->
            <div class="input-group">
                <input type="text"
                      class="form-control chat-input"
                      data-task-id="${task.task_id}"
                      placeholder="Type message...">
                <div class="input-group-append">
                    <button class="btn btn-primary send-btn"
                            data-task-id="${task.task_id}">
                        Send
                    </button>
                </div>
            </div>
                </div>
                `;

                $('#taskSection').append(taskHtml);

                if(task.is_completed == 1) {
                    $('#taskSection .task-card:last')
                        .addClass('task-completed');
                }

                loadTaskComments(task.task_id);
            });
               // 🔥 SET INITIAL TASK COUNT DIRECTLY FROM FETCHED DATA
        var totalTasks = tasks.length;
        var completedTasks = tasks.filter(t => Number(t.is_completed) === 1).length;

        $('#taskCount').text('(' + completedTasks + '/' + totalTasks + ')');
                

            enableTaskSortable();
            updateProgressBar();
        }
    });
});

/* =========================================
   SORTABLE TASKS
========================================= */

function enableTaskSortable() {

    if ($("#taskSection").hasClass("ui-sortable")) {
        $("#taskSection").sortable("destroy");
    }

    $("#taskSection").sortable({
        placeholder: "task-placeholder",
        cursor: "grab",
        opacity: 0.9,
        revert: 150,
        tolerance: "pointer",
        cancel: "input,button",

        update: function() {

            var order = [];

            $('#taskSection .task-card').each(function(index) {
                order.push({
                    task_id: $(this).data('task-id'),
                    position: index + 1
                });
            });

            $.post(base_url + "TRS/update_task_position", { order: order });
        }
    });
}

/* =========================================
   UPDATE PROGRESS
========================================= */

function updateProgressBar(){

    var total = $('#taskSection .task-checkbox').length;
    var completed = $('#taskSection .task-checkbox:checked').length;

    if(total == 0){
        $('#taskProgressBar')
            .css('width','0%')
            .text('0%');
        $('#taskRatio').text('0/0');
        return;
    }

    var percent = Math.round((completed / total) * 100);

    $('#taskProgressBar')
        .css('width', percent + '%')
        .text(percent + '%');

    $('#taskRatio').text(completed + '/' + total);
    $('#taskCount').text('(' + completed + '/' + total + ')');
}
/* =========================================
   TASK CHECKBOX
========================================= */

$(document).on('change', '.task-checkbox', function(){

    var checkbox = $(this);
    var task_id = checkbox.data('id');
    var status = checkbox.is(':checked') ? 1 : 0;

     // 🔥 UI toggle immediately
    checkbox.closest('.task-card')
        .toggleClass('task-completed', status);

    updateProgressBar(); // live update

    $.ajax({
        url: base_url + "TRS/update_task_status",
        type: "POST",
        dataType: "json",
        data: {
            task_id: task_id,
            is_completed: status
        },
        success: function(response){

            if(response.success){

                var ticketCard = $('.ticket-card[data-id="'+response.ticket_id+'"]');

                // 🔥 Update both attr and jQuery cache
                ticketCard.attr('data-can-resolve', response.can_resolve);
                ticketCard.data('can-resolve', Number(response.can_resolve));

                // Optional UI feedback
                if(response.can_resolve == 1){
                    ticketCard.addClass('ready-to-resolve');
                } else {
                    ticketCard.removeClass('ready-to-resolve');
                }

            } else {
                alert("Update failed");
            }

        },
        error: function(){
            alert("Server error");
        }
    });

});
/* =========================================
   ADD TASK
========================================= */

$(document).on('click', '#addTaskBtn', function(){

    if($('#newTaskInput').length) return;

    $('#taskSection').prepend(`
        <div id="newTaskWrapper" class="mb-2">
            <input type="text"
                   id="newTaskInput"
                   class="form-control"
                   placeholder="Enter task and press Enter">
        </div>
    `);

    $('#newTaskInput').focus();
});

$(document).on('keypress', '#newTaskInput', function(e){

    if(e.which == 13){

        var taskTitle = $(this).val().trim();
        if(taskTitle == '') return;

        $.ajax({
            url: base_url + "TRS/add_task",
            type: "POST",
            dataType: "json",
            data: {
                ticket_id: currentTicketId,
                task_title: taskTitle
            },
            success: function(response){

                var task = response.task;

                $('#newTaskWrapper').remove();

                var taskHtml = `
                <div class="task-card mb-2 d-flex align-items-center" data-task-id="${task.task_id}>
                    <input type="checkbox"
                           class="task-checkbox mr-2"
                           data-id="${task.task_id}">
                    <span class="task-title"
                          data-id="${task.task_id}">
                          ${task.task_title}
                    </span>
                </div>
                `;

                $('#taskSection').append(taskHtml);
                updateProgressBar();
            }
        });
    }
});



/* =========================================
   TASK COMMENTS
========================================= */

$(document).on("keypress", ".task-comment-input", function(e){

    if(e.which == 13){

        var input = $(this);
        var taskId = input.data("task-id");
        var comment = input.val().trim();

        if(comment == "") return;

        $.post(base_url + "dashboard/add_task_comment", {
            task_id: taskId,
            comment: comment
        }, function(){

            input.val("");
            loadTaskComments(taskId);
        });
    }
});

function loadTaskComments(taskId){

    $.post(base_url + "dashboard/load_task_comments", {
        task_id: taskId
    }, function(response){

        $("#comments-" + taskId).html(response);
    });
}

$(document).on('click','.confirm-btn',function(){

    var answer = $(this).data('answer');

    $.ajax({
        url: base_url + "TRS/confirm_resolution",
        type:"POST",
        dataType:"json",
        data:{
            ticket_id: currentTicketId,
            answer: answer
        },
        success:function(res){
            if(res.success){
                $('#ticketModal').modal('hide');
                location.reload();
            }
        }
    });

});

</script>
<script>

$(document).ready(function(){

  // CREATE MODAL - Add Task
  $('#addTaskFieldCreate').on('click', function(){
    $('#createTaskWrapper').append(`
      <div class="input-group mb-2">
        <input type="text" name="tasks[]" 
               class="form-control" 
               placeholder="Enter Task">
        <div class="input-group-append">
          <button type="button" 
                  class="btn btn-danger removeTask">X</button>
        </div>
      </div>
    `);
  });


  // EDIT MODAL - Add Task
  $('#addTaskFieldEdit').on('click', function(){
    $('#editTaskWrapper').append(`
      <div class="input-group mb-2">
        <input type="text" name="tasks[]" 
               class="form-control" 
               placeholder="Enter Task">
        <div class="input-group-append">
          <button type="button" 
                  class="btn btn-danger removeTask">X</button>
        </div>
      </div>
    `);
  });


  // REMOVE TASK (Works for both modals)
  $(document).on('click', '.removeTask', function(){
    if ($(this).closest('.input-group').siblings('.input-group').length > 0) {
      $(this).closest('.input-group').remove();
    }
  });

});

$(document).on('click', '.edit-task', function(){

    var taskId = $(this).data('id');

    var titleSpan = $('.task-title[data-id="'+taskId+'"]');
    var currentText = titleSpan.text().trim();

    // Input field bana do
    titleSpan.replaceWith(`
        <input type="text"
               class="form-control form-control-sm edit-input"
               data-id="${taskId}"
               value="${currentText}">
    `);

    $('.edit-input[data-id="'+taskId+'"]').focus();
});

$(document).on('keypress', '.edit-input', function(e){

    if(e.which == 13){

        var input = $(this);
        var taskId = input.data('id');
        var newTitle = input.val().trim();

        if(newTitle == '') return;

        $.post(base_url + "TRS/update_task_title", {
            task_id: taskId,
            task_title: newTitle
        }, function(){

            input.replaceWith(`
                <span class="task-title"
                      data-id="${taskId}">
                      ${newTitle}
                </span>
            `);

        });
    }
});

$(document).on('click', '.send-btn', function(){

    var taskId = $(this).data('task-id');
    var input = $('.chat-input[data-task-id="'+taskId+'"]');
    var message = input.val().trim();

    if(message == '') return;

    $.post(base_url + "dashboard/add_task_comment", {
        task_id: taskId,
        comment: message
    }, function(){

        input.val('');
        loadTaskComments(taskId); // 🔥 SAME FUNCTION
    });
});
$(document).on('keypress', '.chat-input', function(e){

    if(e.which == 13){
        $(this).siblings('.input-group-append')
               .find('.send-btn')
               .click();
    }
});

function loadTaskComments(taskId){

    $.post(base_url + "dashboard/load_task_comments", {
        task_id: taskId
    }, function(response){

        $('#chat-'+taskId).html(response);

        var box = $('#chat-'+taskId);
        box.scrollTop(box[0].scrollHeight);
    });
}


</script>
<script>
var chatInterval;

$('#ticketModal').on('shown.bs.modal', function(){

    chatInterval = setInterval(function(){

        $('.task-card').each(function(){
            var taskId = $(this).data('task-id');
            loadTaskComments(taskId);
        });

    }, 3000);

});

$('#ticketModal').on('hidden.bs.modal', function(){
    clearInterval(chatInterval);
});
</script>
<script>

function loadNotifications(){

    $.ajax({
        url: "<?= base_url('TRS/get_notifications') ?>",
        type: "GET",
        dataType: "json",
        success: function(res){

            let badge = $('#notificationBadge');
            let list = $('#notificationList');
            let header = $('#notificationHeader');

            list.empty();

            if(res.count > 0){

                badge.text(res.count).show();
                header.text(res.count + " Notifications");

                res.notifications.forEach(function(item){

                    list.append(`
                        <a href="<?= base_url('TRS/view/') ?>${item.ticket_id}"
                           class="dropdown-item notification-item"
                           data-id="${item.id}">
                           
                           <i class="fas fa-ticket-alt mr-2 text-primary"></i>
                           ${item.message}
                           
                           <span class="float-right text-muted text-sm">
                               ${item.created_at}
                           </span>
                        </a>
                        <div class="dropdown-divider"></div>
                    `);
                });

            } else {
                badge.hide();
                header.text("0 Notifications");

                list.html(`
                    <span class="dropdown-item text-muted text-center">
                        No new notifications
                    </span>
                `);
            }
        }
    });
}


// Mark as read
$(document).on('click', '.notification-item', function(){

    let id = $(this).data('id');

    $.post("<?= base_url('TRS/mark_notification_read') ?>", {
        id: id
    });

});


// Auto Load
loadNotifications();

setInterval(function(){
    loadNotifications();
}, 3000);

</script>
<script>
    // Add Task (Create Modal)
$(document).on('click', '#addTaskFieldCreate', function () {

    let taskField = `
        <div class="input-group mb-2">
            <input type="text" name="tasks[]" class="form-control" placeholder="Enter Task">
            <div class="input-group-append">
                <button type="button" class="btn btn-danger removeTask">X</button>
            </div>
        </div>
    `;

    $('#taskWrapper').append(taskField);
});


// Remove Task (Dynamic)
$(document).on('click', '.removeTask', function () {

    if ($('#taskWrapper .input-group').length > 0) {
        $(this).closest('.input-group').remove();
    }

});


    </script>
    <script>
function autoRefreshKanban(){

    $.ajax({
        url: base_url + "TRS/get_all_task_counts",
        type: "GET",
        dataType: "json",
        success: function(response){

            response.forEach(function(ticket){

                var card = $('.ticket-card[data-id="'+ticket.ticket_id+'"]');

                if(card.length){

                    card.find('.task-count')
                        .text(ticket.completed + " / " + ticket.total + " Completed");

                }

            });

        }
    });

}

$(document).ready(function(){

    autoRefreshKanban(); // first load

    setInterval(function(){
        autoRefreshKanban();
    }, 2000); // every 1 miniseconds

});
        </script>
</body>
</html>