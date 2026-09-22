<?php

class Dashboard extends Controller
{
    public function index()
    {
        $today = date('Y-m-d');
        $soon = date('Y-m-d', strtotime('+' . DEADLINE_SOON_DAYS . ' days'));

        $tasks = $this->model('M_Tasks');
        $agenda = $this->model('M_Agenda');
        $notes = $this->model('M_Notes');

        $data['judul'] = 'Dashboard';
        $data['today'] = $today;

        // Metrics Tasks
        $data['taskStats'] = $tasks->getStats($today, $soon) ?: [];
        $data['urgentTasks'] = $tasks->getUrgent($today, 5);

        // Metrics Agenda
        $data['agendaStats'] = $agenda->getStats($today) ?: [];
        $data['agendaToday'] = $agenda->getAllAgendas(null, 'today', $today);
        $data['agendaUpcoming'] = $agenda->getAllAgendas(null, 'upcoming', $today, 3);

        // Metrics Notes
        $data['notesTotal'] = $notes->countAll();
        $data['latestNotes'] = $notes->getLatest(3);

        $this->view('templates/header', $data);
        $this->view('dashboard/index', $data);
        $this->view('templates/footer');
    }
}
