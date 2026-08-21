<?php

class Kontak extends Controller
{
    public function index()
    {
        $data['judul'] = 'Kontak';
        $this->view('templates/header', $data);
        $this->view('kontak/index', $data);
        $this->view('templates/footer');
    }
}
