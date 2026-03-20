<?php

class Timer_model extends CI_Model
{
    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    private function diffSeconds($start, $end)
    {
        $startTime = strtotime((string) $start);
        $endTime = strtotime((string) $end);

        if ($startTime === false || $endTime === false) {
            return 0;
        }

        $diff = $endTime - $startTime;
        return $diff > 0 ? $diff : 0;
    }

    public function getTicketTimer($ticket_id)
    {
        return $this->db
            ->where('ticket_id', (int) $ticket_id)
            ->get('ticket_timers')
            ->row_array();
    }

    public function getTicketTimerTotalSeconds(array $timer = null)
    {
        if (empty($timer)) {
            return 0;
        }

        $total = (int) ($timer['total_seconds'] ?? 0);
        if (!empty($timer['is_running']) && !empty($timer['started_at'])) {
            $total += $this->diffSeconds($timer['started_at'], $this->now());
        }

        return $total;
    }

    public function startTicketTimer($ticket_id, $user_id)
    {
        $ticket_id = (int) $ticket_id;
        $user_id = (int) $user_id;
        $now = $this->now();

        $timer = $this->getTicketTimer($ticket_id);
        if (!$timer) {
            $this->db->insert('ticket_timers', [
                'ticket_id' => $ticket_id,
                'user_id' => $user_id,
                'total_seconds' => 0,
                'is_running' => 1,
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            return $this->getTicketTimer($ticket_id);
        }

        if (!empty($timer['stopped_permanently_at'])) {
            return $timer;
        }

        if ((int) $timer['is_running'] === 1) {
            return $timer;
        }

        $this->db->where('ticket_id', $ticket_id)->update('ticket_timers', [
            'user_id' => $user_id,
            'is_running' => 1,
            'started_at' => $now,
            'updated_at' => $now
        ]);

        return $this->getTicketTimer($ticket_id);
    }

    public function pauseTicketTimer($ticket_id, $user_id)
    {
        $ticket_id = (int) $ticket_id;
        $user_id = (int) $user_id;
        $now = $this->now();

        $timer = $this->getTicketTimer($ticket_id);
        if (!$timer) {
            return null;
        }

        if ((int) $timer['is_running'] !== 1 || empty($timer['started_at'])) {
            return $timer;
        }

        $elapsed = $this->diffSeconds($timer['started_at'], $now);
        $total = (int) ($timer['total_seconds'] ?? 0) + $elapsed;

        $this->db->where('ticket_id', $ticket_id)->update('ticket_timers', [
            'user_id' => $user_id,
            'total_seconds' => $total,
            'is_running' => 0,
            'started_at' => null,
            'updated_at' => $now
        ]);

        return $this->getTicketTimer($ticket_id);
    }

    public function stopTicketTimerPermanently($ticket_id, $user_id = null)
    {
        $ticket_id = (int) $ticket_id;
        $now = $this->now();

        $timer = $this->getTicketTimer($ticket_id);
        if (!$timer) {
            return null;
        }

        $total = (int) ($timer['total_seconds'] ?? 0);
        if (!empty($timer['is_running']) && !empty($timer['started_at'])) {
            $total += $this->diffSeconds($timer['started_at'], $now);
        }

        $update = [
            'total_seconds' => $total,
            'is_running' => 0,
            'started_at' => null,
            'stopped_permanently_at' => $now,
            'updated_at' => $now
        ];

        if ($user_id !== null) {
            $update['user_id'] = (int) $user_id;
        }

        $this->db->where('ticket_id', $ticket_id)->update('ticket_timers', $update);

        return $this->getTicketTimer($ticket_id);
    }

    public function getScheduleTaskTimer($schedule_task_id, $execution_date)
    {
        return $this->db
            ->where('schedule_task_id', (int) $schedule_task_id)
            ->where('execution_date', $execution_date)
            ->get('schedule_task_timers')
            ->row_array();
    }

    public function getScheduleTaskTimerTotalSeconds(array $timer = null)
    {
        if (empty($timer)) {
            return 0;
        }

        $total = (int) ($timer['total_seconds'] ?? 0);
        if (!empty($timer['is_running']) && !empty($timer['started_at'])) {
            $total += $this->diffSeconds($timer['started_at'], $this->now());
        }

        return $total;
    }

    public function startScheduleTaskTimer($schedule_task_id, $execution_date, $user_id)
    {
        $schedule_task_id = (int) $schedule_task_id;
        $user_id = (int) $user_id;
        $now = $this->now();

        $timer = $this->getScheduleTaskTimer($schedule_task_id, $execution_date);
        if (!$timer) {
            $this->db->insert('schedule_task_timers', [
                'schedule_task_id' => $schedule_task_id,
                'execution_date' => $execution_date,
                'user_id' => $user_id,
                'total_seconds' => 0,
                'is_running' => 1,
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            return $this->getScheduleTaskTimer($schedule_task_id, $execution_date);
        }

        if ((int) $timer['is_running'] === 1) {
            return $timer;
        }

        $this->db->where('id', (int) $timer['id'])->update('schedule_task_timers', [
            'user_id' => $user_id,
            'is_running' => 1,
            'started_at' => $now,
            'updated_at' => $now
        ]);

        return $this->getScheduleTaskTimer($schedule_task_id, $execution_date);
    }

    public function pauseScheduleTaskTimer($schedule_task_id, $execution_date, $user_id)
    {
        $schedule_task_id = (int) $schedule_task_id;
        $user_id = (int) $user_id;
        $now = $this->now();

        $timer = $this->getScheduleTaskTimer($schedule_task_id, $execution_date);
        if (!$timer) {
            return null;
        }

        if ((int) $timer['is_running'] !== 1 || empty($timer['started_at'])) {
            return $timer;
        }

        $elapsed = $this->diffSeconds($timer['started_at'], $now);
        $total = (int) ($timer['total_seconds'] ?? 0) + $elapsed;

        $this->db->where('id', (int) $timer['id'])->update('schedule_task_timers', [
            'user_id' => $user_id,
            'total_seconds' => $total,
            'is_running' => 0,
            'started_at' => null,
            'updated_at' => $now
        ]);

        return $this->getScheduleTaskTimer($schedule_task_id, $execution_date);
    }

    public function stopScheduleTaskTimer($schedule_task_id, $execution_date, $user_id = null)
    {
        $schedule_task_id = (int) $schedule_task_id;
        $now = $this->now();

        $timer = $this->getScheduleTaskTimer($schedule_task_id, $execution_date);
        if (!$timer) {
            return null;
        }

        $total = (int) ($timer['total_seconds'] ?? 0);
        if (!empty($timer['is_running']) && !empty($timer['started_at'])) {
            $total += $this->diffSeconds($timer['started_at'], $now);
        }

        $update = [
            'total_seconds' => $total,
            'is_running' => 0,
            'started_at' => null,
            'updated_at' => $now
        ];

        if ($user_id !== null) {
            $update['user_id'] = (int) $user_id;
        }

        $this->db->where('id', (int) $timer['id'])->update('schedule_task_timers', $update);

        return $this->getScheduleTaskTimer($schedule_task_id, $execution_date);
    }
}
