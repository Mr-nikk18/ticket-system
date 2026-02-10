<?php $this->load->view('Layout/Header'); ?>

<div class="wrapper">

<div class="content-wrapper">

<section class="content">
<div class="container-fluid">

<div class="row">

<?php foreach ($devBarData as $i => $dev): ?>
  <div class="col-md-4">
    <div class="card card-dark">
      <div class="card-header text-center">
        <b><?= $dev['name'] ?></b>
      </div>
      <div class="card-body">
        <canvas id="pie<?= $i ?>" height="250"></canvas>
      </div>
      <div class="card card-info" style="text-align: center;">
       <button 
  class="btn btn-sm btn-primary" 
  
  onclick="viewMoreTickets(
    <?= $dev['user_id'] ?>,
    '<?= addslashes($dev['name']) ?>'
  )" data-toggle="modal" data-target="#devTicketsModal" >
  View More
</button>


      </div>
    </div>
  </div>
<?php endforeach; ?>

</div>

</div>
</section>

</div> <!-- content-wrapper -->

</div> <!-- wrapper -->


   <div class="modal fade" id="devTicketsModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="btn btn-info modal-header">
        <h4 class="modal-title" ></h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body" id="devModalBody">
        <!-- controller se html yaha aayega -->
         Please wait......
      </div>

    </div>
  </div>
</div>

</div>


<?php $this->load->view('Layout/Footer'); ?>
