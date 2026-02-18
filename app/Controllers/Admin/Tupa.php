<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TupaModel;

class Tupa extends BaseController
{
    protected $tupaModel;

    public function __construct()
    {
        $this->tupaModel = new TupaModel();
    }

    /**
     * Listado de trámites con búsqueda
     */
    public function index()
    {
        $search = $this->request->getGet('search');
        
        if ($search) {
            $data['tramites'] = $this->tupaModel
                ->buscarTramiteAdmin($search)
                ->orderBy('nombre_procedimiento', 'ASC')
                ->paginate(15);
        } else {
            $data['tramites'] = $this->tupaModel
                ->orderBy('nombre_procedimiento', 'ASC')
                ->paginate(15);
        }

        $data['pager'] = $this->tupaModel->pager;
        $data['search'] = $search;
        $data['title'] = 'Gestión TUPA';

        return view('admin/index', $data);
    }

    /**
     * Formulario para nuevo trámite
     */
    public function create()
    {
        $data['title'] = 'Nuevo Trámite TUPA';
        return view('admin/create', $data);
    }

    /**
     * Guardar nuevo trámite
     */
    public function store()
    {
        $rules = [
            'nombre_procedimiento' => 'required|min_length[3]',
            'area' => 'required',
            'derecho_pago' => 'permit_empty',
            'plazo_atencion' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->tupaModel->save([
            'codigo' => $this->request->getPost('codigo'),
            'nombre_procedimiento' => $this->request->getPost('nombre_procedimiento'),
            'descripcion' => $this->request->getPost('descripcion'),
            'requisitos' => $this->request->getPost('requisitos'),
            'derecho_pago' => $this->request->getPost('derecho_pago'),
            'plazo_atencion' => $this->request->getPost('plazo_atencion'),
            'area' => $this->request->getPost('area'),
            'donde_presentar' => $this->request->getPost('donde_presentar'),
            'base_legal' => $this->request->getPost('base_legal'),
            'categoria' => $this->request->getPost('categoria'),
            'keywords' => $this->request->getPost('keywords')
        ]);

        return redirect()->to('/admin/tupa')->with('success', 'Trámite creado exitosamente');
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $data['tramite'] = $this->tupaModel->find($id);

        if (!$data['tramite']) {
            return redirect()->to('/admin/tupa')->with('error', 'Trámite no encontrado');
        }

        $data['title'] = 'Editar Trámite';
        return view('admin/edit', $data);
    }

    /**
     * Actualizar trámite
     */
    public function update($id)
    {
        $rules = [
            'nombre_procedimiento' => 'required|min_length[3]',
            'area' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->tupaModel->update($id, [
            'codigo' => $this->request->getPost('codigo'),
            'nombre_procedimiento' => $this->request->getPost('nombre_procedimiento'),
            'descripcion' => $this->request->getPost('descripcion'),
            'requisitos' => $this->request->getPost('requisitos'),
            'derecho_pago' => $this->request->getPost('derecho_pago'),
            'plazo_atencion' => $this->request->getPost('plazo_atencion'),
            'area' => $this->request->getPost('area'),
            'donde_presentar' => $this->request->getPost('donde_presentar'),
            'base_legal' => $this->request->getPost('base_legal'),
            'categoria' => $this->request->getPost('categoria'),
            'keywords' => $this->request->getPost('keywords')
        ]);

        return redirect()->to('/admin/tupa')->with('success', 'Trámite actualizado exitosamente');
    }

    /**
     * Eliminar trámite
     */
    public function delete($id)
    {
        $this->tupaModel->delete($id);
        return redirect()->to('/admin/tupa')->with('success', 'Trámite eliminado exitosamente');
    }
}
