<?php
$this->load->view('Layout/Header');
$developers = isset($devBarData) && is_array($devBarData) ? $devBarData : [];
?>

<div class="content-wrapper team-status-page">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <h1 class="m-0">Team Status</h1>
          <p class="text-muted mb-0">Review each developer's live ticket distribution and open the detailed ticket list without interrupting the current flow.</p>
        </div>
        <div class="col-sm-5 text-sm-right">
          <div class="team-status-pill">Live Overview</div>
        </div>
      </div>
    </div>
  </div>

  <section class="content pb-4">
    <div class="container-fluid">
      <?php if (empty($developers)): ?>
        <div class="team-status-empty">
          No developer status cards are available right now.
        </div>
      <?php else: ?>
        <div class="row">
          <?php foreach ($developers as $i => $dev): ?>
            <div class="col-xl-4 col-md-6 mb-4 d-flex">
              <div class="card card-dark team-status-card w-100">
                <div class="card-header text-center team-status-card__header">
                  <b><?= htmlspecialchars((string) ($dev['name'] ?? 'Developer')) ?></b>
                </div>
                <div class="card-body team-status-card__body">
                  <canvas id="pie<?= $i ?>" height="250"></canvas>
                </div>
                <div class="team-status-card__footer">
                  <button
                    type="button"
                    class="btn btn-sm btn-primary team-status-card__button"
                    onclick="viewMoreTickets(<?= (int) ($dev['user_id'] ?? 0) ?>, '<?= addslashes((string) ($dev['name'] ?? 'Developer')) ?>')"
                    data-toggle="modal"
                    data-target="#devTicketsModal">
                    View More
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<div class="modal fade" id="devTicketsModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content team-status-modal">
      <div class="modal-header team-status-modal__header">
        <h4 class="modal-title"></h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body" id="devModalBody">
        Please wait......
      </div>
    </div>
  </div>
</div>

<style>
.team-status-page {
  background:
    linear-gradient(180deg, rgba(252, 247, 238, 0.9), rgba(245, 237, 223, 0.92)),
    url('<?= base_url('assets/dist/img/boxed-bg.jpg') ?>') center center / cover fixed no-repeat;
  min-height: calc(100vh - 57px);
  position: relative;
}

.team-status-page .content-header h1 {
  font-size: 1.9rem;
}

.team-status-page .content-header p {
  max-width: 720px;
}

.team-status-pill {
  background: linear-gradient(135deg, #ffd38a, #f59e0b);
  border-radius: 999px;
  color: #4a2d00;
  display: inline-flex;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  padding: 0.45rem 0.9rem;
  text-transform: uppercase;
}

.team-status-card {
  background: rgba(255, 250, 242, 0.92);
  border: 1px solid rgba(94, 64, 20, 0.12);
  border-radius: 22px;
  box-shadow: 0 18px 42px rgba(59, 40, 12, 0.14);
  overflow: hidden;
}

.team-status-card__header {
  background: linear-gradient(135deg, #2d333a, #434a52);
  border-bottom: 0;
  color: #fff7ea;
  font-size: 1rem;
  padding: 1rem;
}

.team-status-card__body {
  align-items: center;
  background: linear-gradient(180deg, rgba(255, 251, 245, 0.98), rgba(249, 241, 229, 0.94));
  display: flex;
  justify-content: center;
  min-height: 320px;
  padding: 1.25rem 1rem 0.75rem;
}

.team-status-card__footer {
  background: rgba(255, 248, 237, 0.95);
  border-top: 1px solid rgba(94, 64, 20, 0.08);
  padding: 0.9rem 1rem 1rem;
  text-align: center;
}

.team-status-card__button {
  border-radius: 12px;
  font-size: 0.95rem;
  font-weight: 600;
  min-width: 180px;
  padding: 0.55rem 1rem;
}

.team-status-empty {
  background: rgba(255, 250, 242, 0.94);
  border: 1px solid rgba(94, 64, 20, 0.1);
  border-radius: 20px;
  box-shadow: 0 18px 42px rgba(59, 40, 12, 0.1);
  color: #6b7280;
  padding: 3rem 1.5rem;
  text-align: center;
}

.team-status-modal {
  border-radius: 18px;
  overflow: hidden;
}

.team-status-modal__header {
  align-items: center;
  background: linear-gradient(135deg, #1f3b5c, #35587b);
  color: #ffffff;
}

.team-status-modal__header .close {
  color: #ffffff;
  opacity: 0.9;
}

@media (max-width: 767.98px) {
  .team-status-page {
    background-attachment: scroll;
  }

  .team-status-card__body {
    min-height: 280px;
  }

  .team-status-pill {
    margin-top: 0.5rem;
  }
}
</style>

<?php $this->load->view('Layout/Footer'); ?>
